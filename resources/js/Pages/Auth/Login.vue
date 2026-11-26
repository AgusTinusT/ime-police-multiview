<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import { Alert, AlertDescription } from "@/Components/ui/alert";
const logoPoliceDuty = "/images/brand/police-duty/masters/police-duty-symbol-color.svg";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: true,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Sign In - SASP Police Multiview" />

    <div class="min-h-screen bg-background text-foreground flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-sm space-y-6">
            <!-- Logo & Header -->
            <div class="text-center space-y-2.5">
                <Link href="/" class="inline-block">
                    <img :src="logoPoliceDuty" class="size-14 object-contain mx-auto" alt="Police Duty Logo" />
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
                        <span class="flex-1 py-1.5 text-xs font-mono text-center font-bold text-primary border-b-2 border-primary">
                            SIGN IN
                        </span>
                        <Link
                            href="/register"
                            class="flex-1 py-1.5 text-xs font-mono text-center font-medium text-muted-foreground hover:text-foreground transition"
                        >
                            REGISTER
                        </Link>
                    </div>

                    <Alert v-if="status" class="border-info/30 bg-info/15 text-info">
                        <AlertDescription class="text-xs font-mono">
                            {{ status }}
                        </AlertDescription>
                    </Alert>

                    <form class="space-y-4 text-xs" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
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
                                placeholder="••••••••"
                            />
                            <p v-if="form.errors.password" class="text-xs text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <Label for="login-remember" class="flex items-center gap-2 cursor-pointer text-muted-foreground hover:text-foreground font-normal">
                                <Checkbox
                                    id="login-remember"
                                    v-model="form.remember"
                                />
                                <span class="text-xs">Ingat saya</span>
                            </Label>
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                class="w-full font-bold text-xs"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? "Signing In..." : "Sign In" }}
                            </Button>
                        </div>
                    </form>

                    <div class="pt-3 border-t border-border text-center">
                        <p class="text-xs text-muted-foreground">
                            Belum memiliki akun?
                            <Link href="/register" class="text-primary hover:underline font-semibold ml-1">
                                Daftar di sini
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
