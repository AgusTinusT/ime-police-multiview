import { reactive, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";

const subscribedIds = reactive(new Set());
const loaded = ref(false);
const loading = ref(false);
const pendingIds = reactive(new Set());
let inflightPromise = null;

export function useOfficerSubscriptions() {
    const ensureLoaded = async (isAuthenticated) => {
        if (!isAuthenticated) {
            subscribedIds.clear();
            loaded.value = false;
            return;
        }
        if (loaded.value) return;
        if (inflightPromise) return inflightPromise;

        loading.value = true;
        inflightPromise = axios
            .get("/api/v1/subscriptions")
            .then((res) => {
                subscribedIds.clear();
                const ids = res.data?.officer_ids || [];
                for (const id of ids) {
                    subscribedIds.add(Number(id));
                }
                loaded.value = true;
            })
            .catch(() => {
                // Silent fail on initial background fetch
            })
            .finally(() => {
                loading.value = false;
                inflightPromise = null;
            });

        return inflightPromise;
    };

    const isSubscribed = (officerId) => subscribedIds.has(Number(officerId));
    const isPending = (officerId) => pendingIds.has(Number(officerId));

    const toggleSubscription = async (officerId, officerName = "") => {
        const id = Number(officerId);
        if (!id || pendingIds.has(id)) return false;

        pendingIds.add(id);
        const currentlySubscribed = subscribedIds.has(id);

        try {
            if (currentlySubscribed) {
                await axios.delete(`/api/v1/officers/${id}/subscribe`);
                subscribedIds.delete(id);
                toast.info(
                    officerName
                        ? `Berhenti mengikuti notifikasi live ${officerName}.`
                        : "Berhenti mengikuti notifikasi live perwira.",
                );
                return false;
            } else {
                await axios.post(`/api/v1/officers/${id}/subscribe`, {
                    notify_in_app: true,
                    notify_web_push: true,
                });
                subscribedIds.add(id);
                toast.success(
                    officerName
                        ? `Mengikuti notifikasi live ${officerName}.`
                        : "Berhasil mengikuti notifikasi live perwira.",
                );
                return true;
            }
        } catch (err) {
            const msg =
                err?.response?.data?.message ||
                "Gagal memperbarui langganan notifikasi.";
            toast.error(msg);
            return currentlySubscribed;
        } finally {
            pendingIds.delete(id);
        }
    };

    return {
        subscribedIds,
        loaded,
        loading,
        ensureLoaded,
        isSubscribed,
        isPending,
        toggleSubscription,
    };
}
