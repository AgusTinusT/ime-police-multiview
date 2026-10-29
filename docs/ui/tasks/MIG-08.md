# MIG-08 — Auth, Profil Akun, sisa komponen Breeze

**Prioritas:** 🟡 P2 · **Bergantung:** MIG-01 · **Resep:** `GEMINI-UI-PLAYBOOK.md`
**Commit:** `refactor(ui): MIG-08 auth and profile pages`

## File
`Pages/Auth/*.vue` (Login, Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword), `Pages/Profile/Edit.vue`, `Pages/Profile/Partials/*.vue`, `Components/AuthModal.vue`, `Components/Modal.vue`, `Components/TextInput.vue`, `Components/InputLabel.vue`, `Components/InputError.vue`, `Components/Checkbox.vue`, `Components/PrimaryButton.vue`, `Components/SecondaryButton.vue`, `Components/DangerButton.vue`, `Components/ApplicationLogo.vue`

## Catatan khusus
- Form auth → `Card` di tengah (`max-w-sm`) + `Label`/`Input`/`Checkbox`/`Button`; error → `text-xs text-destructive`.
- `AuthModal` → `Dialog` + `Tabs` (Masuk / Daftar).
- Hapus komponen Breeze (`Modal`, `TextInput`, `InputLabel`, `InputError`, `Checkbox`, `PrimaryButton`, `SecondaryButton`, `DangerButton`) **setelah** tidak ada pemakai (`grep -rn "TextInput" resources/js`). `ApplicationLogo` tetap.
- Hapus akun → `AlertDialog` + konfirmasi password.

## Selesai jika
- [ ] File lolos `lint:ui`; komponen Breeze yang tak terpakai dihapus.
- [ ] Test `tests/Feature/Auth` & `ProfileTest` hijau.
