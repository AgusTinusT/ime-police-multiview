<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import logoSaspColor from "@/Components/Icons/SASP_256.jpg";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Register - SASP Police Multiview" />

    <div class="min-h-screen bg-background text-foreground flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-sm space-y-6">
            <!-- Logo & Header -->
            <div class="text-center space-y-2.5">
                <Link href="/" class="inline-block">
                    <img :src="logoSaspColor" class="size-14 rounded-full mx-auto border border-border" alt="SASP Logo" />
                </Link>
                <div>
                    <h1 class="text-base font-bold text-foreground font-mono tracking-wider uppercase">
                        SASP POLICE MULTIVIEW
                    </h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        IME Roleplay Community
                    </p>
                </div>
            </div>

            <!-- Card Container -->
            <Card>
                <CardContent class="p-6 space-y-5">
                    <!-- Tab Switcher -->
                    <div class="flex border-b border-border pb-3">
                        <Link
                            href="/login"
                            class="flex-1 py-1.5 text-xs font-mono text-center font-medium text-muted-foreground hover:text-foreground transition"
                        >
                            SIGN IN
                        </Link>
                        <span class="flex-1 py-1.5 text-xs font-mono text-center font-bold text-primary border-b-2 border-primary">
                            REGISTER
                        </span>
                    </div>

                    <form class="space-y-4 text-xs" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label for="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                placeholder="John Doe"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="nama@email.com"
                            />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                placeholder="Minimal 8 karakter"
                            />
                            <p v-if="form.errors.password" class="text-xs text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi password"
                            />
                            <p v-if="form.errors.password_confirmation" class="text-xs text-destructive">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                class="w-full font-bold text-xs"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? "Creating Account..." : "Register" }}
                            </Button>
                        </div>
                    </form>

                    <div class="pt-3 border-t border-border text-center">
                        <p class="text-xs text-muted-foreground">
                            Sudah memiliki akun?
                            <Link href="/login" class="text-primary hover:underline font-semibold ml-1">
                                Masuk di sini
                            </Link>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Minimalist Back Link -->
            <div class="text-center">
                <Link href="/" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition">
                    <ArrowLeft class="size-3.5" />
                    <span>Kembali ke Dashboard</span>
                </Link>
            </div>
        </div>
    </div>
</template>
