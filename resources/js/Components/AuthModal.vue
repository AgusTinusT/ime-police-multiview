<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { User, KeyRound, Sparkles } from "@lucide/vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/Components/ui/tabs";

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close"]);

const activeTab = ref("login"); // 'login' or 'register'

// Login Form
const loginForm = useForm({
    email: "",
    password: "",
    remember: true,
});

// Register Form
const registerForm = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const handleLogin = () => {
    loginForm.post("/login", {
        onSuccess: () => {
            loginForm.reset("password");
            emit("close");
        },
        onError: () => {
            // Keep modal open to show validation errors
        },
    });
};

const handleRegister = () => {
    registerForm.post("/register", {
        onSuccess: () => {
            registerForm.reset("password", "password_confirmation");
            emit("close");
        },
        onError: () => {
            // Keep modal open to show validation errors
        },
    });
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(!$event) && emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center gap-2.5">
                    <div class="size-8 rounded-lg bg-primary/15 border border-primary/30 flex items-center justify-center text-primary">
                        <User class="size-4" />
                    </div>
                    <div>
                        <DialogTitle class="text-sm font-bold font-mono tracking-wider uppercase leading-none">
                            IME POLICE ACCOUNT
                        </DialogTitle>
                        <DialogDescription class="text-xs mt-1">
                            Masuk atau daftar untuk sinkronisasi cloud
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="login" class="text-xs font-mono uppercase tracking-wider gap-1.5">
                        <KeyRound class="size-3.5" />
                        Sign In
                    </TabsTrigger>
                    <TabsTrigger value="register" class="text-xs font-mono uppercase tracking-wider gap-1.5">
                        <Sparkles class="size-3.5" />
                        Register
                    </TabsTrigger>
                </TabsList>

                <!-- LOGIN FORM -->
                <TabsContent value="login" class="pt-4">
                    <form class="space-y-4" @submit.prevent="handleLogin">
                        <div class="space-y-1.5">
                            <Label for="modal-login-email" class="text-xs font-mono">Email Address</Label>
                            <Input
                                id="modal-login-email"
                                v-model="loginForm.email"
                                type="email"
                                required
                                placeholder="perwira@ime-rp.com"
                            />
                            <p v-if="loginForm.errors.email" class="text-xs text-destructive">
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="modal-login-password" class="text-xs font-mono">Password</Label>
                            <Input
                                id="modal-login-password"
                                v-model="loginForm.password"
                                type="password"
                                required
                                placeholder="••••••••"
                            />
                            <p v-if="loginForm.errors.password" class="text-xs text-destructive">
                                {{ loginForm.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <Label for="modal-login-remember" class="flex items-center gap-2 text-muted-foreground cursor-pointer font-normal">
                                <Checkbox
                                    id="modal-login-remember"
                                    v-model="loginForm.remember"
                                />
                                <span>Remember me</span>
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            class="w-full font-bold text-xs"
                            :disabled="loginForm.processing"
                        >
                            {{ loginForm.processing ? "Signing in..." : "Sign In to Account" }}
                        </Button>
                    </form>
                </TabsContent>

                <!-- REGISTER FORM -->
                <TabsContent value="register" class="pt-4">
                    <form class="space-y-4" @submit.prevent="handleRegister">
                        <div class="space-y-1.5">
                            <Label for="modal-register-name" class="text-xs font-mono">Full Name / Callname</Label>
                            <Input
                                id="modal-register-name"
                                v-model="registerForm.name"
                                type="text"
                                required
                                placeholder="Agus Tinus Turnip"
                            />
                            <p v-if="registerForm.errors.name" class="text-xs text-destructive">
                                {{ registerForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="modal-register-email" class="text-xs font-mono">Email Address</Label>
                            <Input
                                id="modal-register-email"
                                v-model="registerForm.email"
                                type="email"
                                required
                                placeholder="nama@email.com"
                            />
                            <p v-if="registerForm.errors.email" class="text-xs text-destructive">
                                {{ registerForm.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="modal-register-password" class="text-xs font-mono">Password</Label>
                            <Input
                                id="modal-register-password"
                                v-model="registerForm.password"
                                type="password"
                                required
                                placeholder="Minimal 8 karakter"
                            />
                            <p v-if="registerForm.errors.password" class="text-xs text-destructive">
                                {{ registerForm.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="modal-register-password-confirm" class="text-xs font-mono">Confirm Password</Label>
                            <Input
                                id="modal-register-password-confirm"
                                v-model="registerForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Ulangi password"
                            />
                        </div>

                        <Button
                            type="submit"
                            class="w-full font-bold text-xs"
                            :disabled="registerForm.processing"
                        >
                            {{ registerForm.processing ? "Creating Account..." : "Create Member Account" }}
                        </Button>
                    </form>
                </TabsContent>
            </Tabs>

            <div class="pt-3 border-t border-border text-xs text-muted-foreground text-center">
                <span>Custom Squads &amp; Multi-device Sync • IME Roleplay Community</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
