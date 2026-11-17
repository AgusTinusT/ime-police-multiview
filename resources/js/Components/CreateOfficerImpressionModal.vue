<script setup>
import { ref } from "vue";
import { toast } from "vue-sonner";
import { SquarePen } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    officer: { type: Object, default: () => ({}) },
    stream: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["close", "submit"]);

const authorName = ref("");
const impressionType = ref("COMMUNITY_NOTE");
const content = ref("");

const handleSubmit = () => {
    if (!content.value.trim()) {
        toast.warning("Harap tuliskan catatan kesan atau pengalaman Anda!");
        return;
    }

    emit("submit", {
        author_name: authorName.value.trim() || "Penonton Komunitas",
        impression_type: impressionType.value,
        content: content.value.trim(),
    });

    content.value = "";
    emit("close");
};
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (val) => {
                if (!val) emit('close');
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 font-mono text-sm">
                    <SquarePen class="w-4 h-4 text-primary" />
                    <span>Tulis Kesan & Catatan Perwira</span>
                </DialogTitle>
                <DialogDescription class="font-mono text-xs">
                    Perwira:
                    {{
                        officer?.officer_name ||
                        stream?.officer?.officer_name ||
                        "Trooper"
                    }}
                </DialogDescription>
            </DialogHeader>

            <!-- FORM BODY -->
            <div class="space-y-3.5 font-mono text-xs">
                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Tipe Kesan / Catatan</label
                    >
                    <select
                        v-model="impressionType"
                        class="w-full h-9 bg-background border border-input rounded-md px-3 py-1.5 text-foreground focus:border-ring outline-hidden text-xs cursor-pointer"
                    >
                        <option value="COMMUNITY_NOTE">
                            Catatan Komunitas & Rekap RP
                        </option>
                        <option value="TACTICAL_RATING">
                            Rating & Ulasan Gaya Taktis / Pursuit
                        </option>
                        <option value="FUNNY_MOMENT">Momen Lucu</option>
                        <option value="REPUTATION">
                            Catatan Reputasi & Integritas RP
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Nama Penulis / Panggilan Anda</label
                    >
                    <Input
                        v-model="authorName"
                        type="text"
                        placeholder="Contoh: Citizen_V"
                        class="h-9 text-xs"
                    />
                </div>

                <div>
                    <label class="block text-foreground font-medium mb-1"
                        >Isi Kesan & Catatan Lore *</label
                    >
                    <Textarea
                        v-model="content"
                        rows="4"
                        placeholder="Bagikan ulasan atau rekap gaya patroli perwira ini..."
                        class="text-xs leading-relaxed resize-none"
                    />
                </div>
            </div>

            <!-- FOOTER -->
            <DialogFooter class="pt-3 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="emit('close')"
                    class="text-xs font-mono"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    size="sm"
                    @click="handleSubmit"
                    class="text-xs font-mono font-semibold"
                >
                    Kirim Catatan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
