<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

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

            <div class="space-y-1.5">
                <Label for="password">Password Baru</Label>

                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <p v-if="form.errors.password" class="text-xs text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="password_confirmation">Konfirmasi Password Baru</Label>

                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <p v-if="form.errors.password_confirmation" class="text-xs text-destructive">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    Reset Password
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
