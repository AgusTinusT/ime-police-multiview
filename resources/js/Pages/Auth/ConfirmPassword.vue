<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const form = useForm({
    password: "",
});

const submit = () => {
    form.post(route("password.confirm"), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-4 text-xs text-muted-foreground leading-relaxed">
            This is a secure area of the application. Please confirm your
            password before continuing.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-1.5">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="••••••••"
                />
                <p v-if="form.errors.password" class="text-xs text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="pt-2 flex justify-end">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
