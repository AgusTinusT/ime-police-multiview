<script setup>
import { computed } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Alert, AlertDescription } from "@/Components/ui/alert";

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent",
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-4 text-xs text-muted-foreground leading-relaxed">
            Thanks for signing up! Before getting started, could you verify your
            email address by clicking on the link we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <Alert
            v-if="verificationLinkSent"
            class="mb-4 border-success/30 bg-success/15 text-success"
        >
            <AlertDescription class="text-xs font-mono">
                A new verification link has been sent to the email address you
                provided during registration.
            </AlertDescription>
        </Alert>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between gap-3">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    Resend Verification Email
                </Button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-xs text-muted-foreground hover:text-foreground underline font-mono transition"
                >
                    Log Out
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
