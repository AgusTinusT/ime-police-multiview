<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Alert, AlertDescription } from "@/Components/ui/alert";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-4 text-xs text-muted-foreground leading-relaxed">
            Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.
        </div>

        <Alert v-if="status" class="mb-4 border-info/30 bg-info/15 text-info">
            <AlertDescription class="text-xs font-mono">
                {{ status }}
            </AlertDescription>
        </Alert>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-1.5">
                <Label for="email">Email Address</Label>

                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />

                <p v-if="form.errors.email" class="text-xs text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    Email Password Reset Link
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
