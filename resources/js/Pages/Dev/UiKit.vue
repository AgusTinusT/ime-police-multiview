<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import {
    DeptBadge,
    EmptyState,
    LiveBadge,
    OfficerAvatar,
    PageContainer,
    PageHeader,
    SectionHeader,
    StatTile,
    TacticalFrame,
} from "@/Components/tactical";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    NativeSelect,
    NativeSelectOption,
} from "@/Components/ui/native-select";
import { Checkbox } from "@/Components/ui/checkbox";
import { Switch } from "@/Components/ui/switch";
import { RadioGroup, RadioGroupItem } from "@/Components/ui/radio-group";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/Components/ui/alert-dialog";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/Components/ui/sheet";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Skeleton } from "@/Components/ui/skeleton";
import { Separator } from "@/Components/ui/separator";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/Components/ui/empty";
import { Spinner } from "@/Components/ui/spinner";
import { Progress } from "@/Components/ui/progress";
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/Components/ui/pagination";
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/Components/ui/breadcrumb";
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import { AspectRatio } from "@/Components/ui/aspect-ratio";
import { toast } from "vue-sonner";
import {
    Bell,
    CheckCircle2,
    Inbox,
    MoreHorizontal,
    Plus,
    Radio,
    ShieldCheck,
    TriangleAlert,
    Users,
} from "@lucide/vue";

const colorTokens = [
    { name: "--background / --foreground", bg: "bg-background", text: "text-foreground", role: "Latar halaman / teks utama" },
    { name: "--card / --card-foreground", bg: "bg-card", text: "text-card-foreground", role: "Kartu, panel" },
    { name: "--popover / --popover-foreground", bg: "bg-popover", text: "text-popover-foreground", role: "Dropdown, popover, tooltip" },
    { name: "--primary / --primary-foreground", bg: "bg-primary", text: "text-primary-foreground", role: "Aksi utama, link, fokus" },
    { name: "--secondary / --secondary-foreground", bg: "bg-secondary", text: "text-secondary-foreground", role: "Tombol sekunder" },
    { name: "--muted / --muted-foreground", bg: "bg-muted", text: "text-muted-foreground", role: "Area redup / teks sekunder" },
    { name: "--accent / --accent-foreground", bg: "bg-accent", text: "text-accent-foreground", role: "Hover item menu/list" },
    { name: "--destructive", bg: "bg-destructive", text: "text-destructive-foreground", role: "Hapus, error, banned" },
    { name: "--border / --input / --ring", bg: "bg-border", text: "text-foreground", role: "Garis, border input, fokus" },
    { name: "--chart-1…5", bg: "bg-chart-1", text: "text-primary-foreground", role: "Grafik" },
    { name: "--sidebar", bg: "bg-sidebar", text: "text-sidebar-foreground", role: "Sidebar admin" },
    { name: "--success", bg: "bg-success", text: "text-success-foreground", role: "Berhasil, approved, online" },
    { name: "--warning", bg: "bg-warning", text: "text-warning-foreground", role: "Pending, peringatan" },
    { name: "--info", bg: "bg-info", text: "text-info-foreground", role: "Informasi" },
    { name: "--live", bg: "bg-live", text: "text-live-foreground", role: "Indikator siaran LIVE" },
    { name: "--dept-lspd", bg: "bg-dept-lspd", text: "text-primary-foreground", role: "Identitas LSPD" },
    { name: "--dept-bcso", bg: "bg-dept-bcso", text: "text-primary-foreground", role: "Identitas BCSO" },
    { name: "--dept-sasp", bg: "bg-dept-sasp", text: "text-primary-foreground", role: "Identitas SASP" },
    { name: "--dept-sapr", bg: "bg-dept-sapr", text: "text-primary-foreground", role: "Identitas SAPR" },
    { name: "--hud-line", bg: "bg-hud", text: "text-foreground", role: "Garis aksen taktis" },
];

const typographyRoles = [
    {
        role: "Judul hero",
        classes: "font-heading text-4xl lg:text-6xl font-bold tracking-tight",
        sample: "SASP TACTICAL COMMAND",
    },
    {
        role: "Judul halaman",
        classes: "font-heading text-3xl font-bold",
        sample: "Direktori Perwira Aktif",
    },
    {
        role: "Judul section",
        classes: "font-heading text-2xl font-semibold",
        sample: "Siaran Patroli Langsung",
    },
    {
        role: "Judul kartu",
        classes: "text-base font-semibold",
        sample: "Statistik Jam Patroli Mingguan",
    },
    {
        role: "Teks utama",
        classes: "text-sm",
        sample: "Pantau seluruh unit patroli San Andreas State Police secara real-time dalam satu layar.",
    },
    {
        role: "Pendukung",
        classes: "text-xs text-muted-foreground",
        sample: "Diperbarui otomatis setiap 60 detik melalui telemetri YouTube Data API.",
    },
    {
        role: "Label taktis",
        classes: "font-mono text-xs uppercase tracking-wider",
        sample: "CALLSIGN // 10-8 ON DUTY",
    },
];

const formCallsign = ref("SASP-101");
const formBio = ref("Unit patroli jalan raya San Andreas State Police.");
const formDept = ref("SASP");
const formNativeRank = ref("Sergeant");
const formVerified = ref(true);
const formLiveNotify = ref(true);
const formDutyStatus = ref("on-duty");
const activeTab = ref("overview");
const progressVal = ref(68);

const sampleOfficers = [
    { callsign: "101", name: "Capt. Vance", dept: "SASP", status: "LIVE", viewers: 1420 },
    { callsign: "204", name: "Sgt. Miller", dept: "LSPD", status: "LIVE", viewers: 890 },
    { callsign: "312", name: "Dep. Reyes", dept: "BCSO", status: "OFFLINE", viewers: 0 },
];

const frameVariants = ["panel", "hero", "tag"];
const frameAccents = ["primary", "info", "live"];
</script>

<template>
    <Head title="Tactical UI Kit — Katalog Desain" />

    <TacticalLayout>
        <PageContainer class="py-8 lg:py-12 space-y-12">
            <PageHeader
                eyebrow="DESIGN SYSTEM // DS-03"
                title="Tactical UI Kit Catalog"
                description="Katalog referensi hidup untuk token warna, tipografi, komponen shadcn-vue, dan komponen taktis khusus proyek."
            >
                <template #actions>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="toast.success('Toast berhasil ditampilkan dari katalog UI Kit.')"
                    >
                        <Bell class="size-4" />
                        Uji Toast Sonner
                    </Button>
                </template>
            </PageHeader>

            <!-- 1. Token Warna -->
            <section class="space-y-4">
                <SectionHeader
                    title="1. Token Warna"
                    description="Seluruh warna UI wajib memakai token semantik di bawah ini (tanpa palet mentah atau hex)."
                />
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="token in colorTokens" :key="token.name" class="py-4">
                        <CardContent class="space-y-3 px-4">
                            <div
                                :class="[
                                    'flex h-14 items-center justify-between rounded-md border border-border px-3 font-mono text-xs font-semibold',
                                    token.bg,
                                    token.text,
                                ]"
                            >
                                <span>{{ token.bg }}</span>
                                <span>Aa</span>
                            </div>
                            <div>
                                <p class="font-mono text-xs font-semibold text-foreground">{{ token.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ token.role }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <Card class="py-4">
                    <CardContent class="px-4">
                        <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;div class="bg-card text-card-foreground border border-border"&gt;...&lt;/div&gt;
&lt;span class="bg-success/15 text-success"&gt;ONLINE&lt;/span&gt;</code></pre>
                    </CardContent>
                </Card>
            </section>

            <Separator />

            <!-- 2. Tipografi -->
            <section class="space-y-4">
                <SectionHeader
                    title="2. Tipografi"
                    description="Skala tipografi Rajdhani (heading), Plus Jakarta Sans (body), dan JetBrains Mono (label taktis)."
                />
                <Card>
                    <CardContent class="divide-y divide-border">
                        <div
                            v-for="item in typographyRoles"
                            :key="item.role"
                            class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 lg:flex-row lg:items-baseline lg:justify-between"
                        >
                            <div class="space-y-1">
                                <span class="font-mono text-xs text-primary">{{ item.role }}</span>
                                <p :class="[item.classes, 'text-foreground']">{{ item.sample }}</p>
                            </div>
                            <pre class="shrink-0 rounded-md bg-background px-2.5 py-1 font-mono text-xs text-muted-foreground"><code>{{ item.classes }}</code></pre>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Separator />

            <!-- 3. Komponen shadcn-vue -->
            <section class="space-y-6">
                <SectionHeader
                    title="3. Komponen shadcn-vue"
                    description="Komponen dasar di @/Components/ui/* dengan seluruh varian dan ukuran."
                />

                <!-- Button & Badge -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Button</CardTitle>
                            <CardDescription>Varian: default, secondary, outline, ghost, destructive, link; Ukuran: sm, default, lg, icon.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Button variant="default">Default</Button>
                                <Button variant="secondary">Secondary</Button>
                                <Button variant="outline">Outline</Button>
                                <Button variant="ghost">Ghost</Button>
                                <Button variant="destructive">Destructive</Button>
                                <Button variant="link">Link</Button>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Button size="sm">Small</Button>
                                <Button size="default">Default</Button>
                                <Button size="lg">Large</Button>
                                <Button size="icon" aria-label="Tambah item">
                                    <Plus class="size-4" />
                                </Button>
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;Button variant="default" size="sm"&gt;Aksi&lt;/Button&gt;
&lt;Button variant="destructive"&gt;Hapus&lt;/Button&gt;</code></pre>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Badge</CardTitle>
                            <CardDescription>Varian standar + varian proyek: success, warning, info, live.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="default">Default</Badge>
                                <Badge variant="secondary">Secondary</Badge>
                                <Badge variant="outline">Outline</Badge>
                                <Badge variant="destructive">Destructive</Badge>
                                <Badge variant="success">Success</Badge>
                                <Badge variant="warning">Warning</Badge>
                                <Badge variant="info">Info</Badge>
                                <Badge variant="live">Live</Badge>
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;Badge variant="success"&gt;APPROVED&lt;/Badge&gt;
&lt;Badge variant="warning"&gt;PENDING&lt;/Badge&gt;</code></pre>
                        </CardContent>
                    </Card>
                </div>

                <!-- Form Controls -->
                <Card>
                    <CardHeader>
                        <CardTitle>Kontrol Formulir</CardTitle>
                        <CardDescription>Input, Textarea, Label, Select, NativeSelect, Checkbox, Switch, RadioGroup.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="demo-callsign">Callsign</Label>
                                <Input id="demo-callsign" v-model="formCallsign" placeholder="Masukkan callsign" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="demo-bio">Deskripsi Singkat</Label>
                                <Textarea id="demo-bio" v-model="formBio" rows="3" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Departemen (Select)</Label>
                                <Select v-model="formDept">
                                    <SelectTrigger aria-label="Pilih departemen">
                                        <SelectValue placeholder="Pilih departemen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="LSPD">LSPD</SelectItem>
                                        <SelectItem value="BCSO">BCSO</SelectItem>
                                        <SelectItem value="SASP">SASP</SelectItem>
                                        <SelectItem value="SAPR">SAPR</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="demo-native-select">Pangkat (NativeSelect)</Label>
                                <NativeSelect id="demo-native-select" v-model="formNativeRank">
                                    <NativeSelectOption value="Officer">Officer</NativeSelectOption>
                                    <NativeSelectOption value="Sergeant">Sergeant</NativeSelectOption>
                                    <NativeSelectOption value="Captain">Captain</NativeSelectOption>
                                </NativeSelect>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="demo-check" v-model="formVerified" />
                                <Label for="demo-check">Terverifikasi YouTube Resmi</Label>
                            </div>
                            <div class="flex items-center justify-between rounded-md border border-border p-3">
                                <Label for="demo-switch">Notifikasi Siaran Live</Label>
                                <Switch id="demo-switch" v-model="formLiveNotify" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Status Tugas (RadioGroup)</Label>
                                <RadioGroup v-model="formDutyStatus" class="flex gap-4">
                                    <div class="flex items-center gap-2">
                                        <RadioGroupItem id="r-on" value="on-duty" />
                                        <Label for="r-on">10-8 On Duty</Label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <RadioGroupItem id="r-off" value="off-duty" />
                                        <Label for="r-off">10-7 Off Duty</Label>
                                    </div>
                                </RadioGroup>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <pre class="w-full overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;Label for="callsign"&gt;Callsign&lt;/Label&gt;
&lt;Input id="callsign" v-model="form.callsign" /&gt;</code></pre>
                    </CardFooter>
                </Card>

                <!-- Overlays & Feedback -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Overlay & Menu</CardTitle>
                            <CardDescription>Dialog, AlertDialog, Sheet, Popover, DropdownMenu, Tooltip.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Dialog>
                                    <DialogTrigger as-child>
                                        <Button variant="outline" size="sm">Buka Dialog</Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle>Verifikasi Klaim Perwira</DialogTitle>
                                            <DialogDescription>Pastikan kode verifikasi sudah ditempel di deskripsi channel YouTube Anda.</DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <Button size="sm">Konfirmasi</Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <Button variant="destructive" size="sm">AlertDialog Hapus</Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Hapus tautan bio ini?</AlertDialogTitle>
                                            <AlertDialogDescription>Tindakan ini permanen dan akan dicatat di log audit.</AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <AlertDialogAction>Hapus</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <Sheet>
                                    <SheetTrigger as-child>
                                        <Button variant="secondary" size="sm">Buka Sheet</Button>
                                    </SheetTrigger>
                                    <SheetContent>
                                        <SheetHeader>
                                            <SheetTitle>Drawer Komando</SheetTitle>
                                            <SheetDescription>Panel samping untuk filter atau daftar cepat.</SheetDescription>
                                        </SheetHeader>
                                    </SheetContent>
                                </Sheet>

                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button variant="outline" size="sm">Popover</Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-64 text-xs">
                                        Informasi ringkas telemetri unit patroli aktif.
                                    </PopoverContent>
                                </Popover>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" aria-label="Menu aksi">
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Aksi Perwira</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem>Lihat Profil</DropdownMenuItem>
                                        <DropdownMenuItem>Buka Multiview</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="sm">Hover Tooltip</Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Status sinkronisasi YouTube aktif</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;Dialog v-model:open="open"&gt;
  &lt;DialogContent&gt;...&lt;/DialogContent&gt;
&lt;/Dialog&gt;</code></pre>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Alert, Toast, Progress, Spinner, Skeleton</CardTitle>
                            <CardDescription>Komponen umpan balik status dan pemuatan data.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <Alert>
                                <CheckCircle2 class="size-4" />
                                <AlertTitle>Sinkronisasi Berhasil</AlertTitle>
                                <AlertDescription>Data siaran langsung telah diperbarui.</AlertDescription>
                            </Alert>
                            <div class="flex flex-wrap items-center gap-2">
                                <Button size="sm" variant="outline" @click="toast.success('Profil berhasil disimpan')">Toast Sukses</Button>
                                <Button size="sm" variant="outline" @click="toast.error('Gagal memuat stream')">Toast Error</Button>
                                <Button size="sm" variant="outline" @click="toast.info('Pembaruan jadwal patroli')">Toast Info</Button>
                            </div>
                            <div class="flex items-center gap-4">
                                <Spinner class="size-5 text-primary" />
                                <Progress :model-value="progressVal" class="flex-1" />
                                <span class="font-mono text-xs text-muted-foreground">{{ progressVal }}%</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Skeleton class="size-10 rounded-full" />
                                <div class="space-y-2 flex-1">
                                    <Skeleton class="h-3 w-1/3" />
                                    <Skeleton class="h-3 w-2/3" />
                                </div>
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>toast.success('Profil berhasil disimpan')
&lt;Skeleton class="h-4 w-32" /&gt;</code></pre>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tabs, Table, Breadcrumb, Pagination, ScrollArea, Carousel, AspectRatio, Empty -->
                <Card>
                    <CardHeader>
                        <CardTitle>Navigasi, Data & Media</CardTitle>
                        <CardDescription>Breadcrumb, Tabs, Table, Avatar, ScrollArea, Pagination, Carousel, AspectRatio, Empty.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <Breadcrumb>
                            <BreadcrumbList>
                                <BreadcrumbItem>
                                    <BreadcrumbLink href="/dashboard">Komando</BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator />
                                <BreadcrumbItem>
                                    <BreadcrumbPage>UI Kit</BreadcrumbPage>
                                </BreadcrumbItem>
                            </BreadcrumbList>
                        </Breadcrumb>

                        <Tabs v-model="activeTab">
                            <TabsList>
                                <TabsTrigger value="overview">Tabel Unit</TabsTrigger>
                                <TabsTrigger value="media">Carousel & Media</TabsTrigger>
                                <TabsTrigger value="empty">Empty Base</TabsTrigger>
                            </TabsList>
                            <TabsContent value="overview" class="space-y-4 pt-3">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Perwira</TableHead>
                                            <TableHead>Departemen</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead class="text-right">Penonton</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="off in sampleOfficers" :key="off.callsign">
                                            <TableCell class="font-medium">
                                                <div class="flex items-center gap-2">
                                                    <Avatar class="size-7">
                                                        <AvatarImage src="" :alt="off.name" />
                                                        <AvatarFallback class="font-mono text-xs">{{ off.callsign }}</AvatarFallback>
                                                    </Avatar>
                                                    <span>{{ off.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell><DeptBadge :dept="off.dept" /></TableCell>
                                            <TableCell>
                                                <LiveBadge v-if="off.status === 'LIVE'" :viewers="off.viewers" />
                                                <Badge v-else variant="secondary">OFFLINE</Badge>
                                            </TableCell>
                                            <TableCell class="text-right font-mono tabular-nums">{{ off.viewers }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>

                                <Pagination :total="30" :items-per-page="10" :default-page="1">
                                    <PaginationContent>
                                        <PaginationPrevious />
                                        <PaginationItem :value="1" :is-active="true">1</PaginationItem>
                                        <PaginationItem :value="2">2</PaginationItem>
                                        <PaginationNext />
                                    </PaginationContent>
                                </Pagination>
                            </TabsContent>

                            <TabsContent value="media" class="grid gap-6 pt-3 md:grid-cols-2">
                                <div class="px-10">
                                    <Carousel class="w-full">
                                        <CarouselContent>
                                            <CarouselItem v-for="slide in 3" :key="slide">
                                                <div class="rounded-lg border border-border bg-muted/40 p-6 text-center">
                                                    <p class="font-heading text-xl font-bold">Slide Komando #{{ slide }}</p>
                                                    <p class="text-xs text-muted-foreground">Pratinjau komponen Embla Carousel</p>
                                                </div>
                                            </CarouselItem>
                                        </CarouselContent>
                                        <CarouselPrevious />
                                        <CarouselNext />
                                    </Carousel>
                                </div>
                                <div class="space-y-2">
                                    <AspectRatio :ratio="16 / 9" class="flex items-center justify-center rounded-lg border border-border bg-muted/30">
                                        <span class="font-mono text-xs text-muted-foreground">AspectRatio 16:9</span>
                                    </AspectRatio>
                                    <ScrollArea class="h-20 rounded-md border border-border p-3 text-xs text-muted-foreground">
                                        Log radio taktis: Unit 101 merespons kode 3 di Legion Square. Unit 204 melakukan blokade jalur utara. Unit udara bersiaga.
                                    </ScrollArea>
                                </div>
                            </TabsContent>

                            <TabsContent value="empty" class="pt-3">
                                <Empty class="border border-dashed border-border py-8">
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Inbox class="size-5 text-muted-foreground" />
                                        </EmptyMedia>
                                        <EmptyTitle>Komponen Empty Dasar</EmptyTitle>
                                        <EmptyDescription>Digunakan oleh pembungkus taktis EmptyState.</EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            </TabsContent>
                        </Tabs>

                        <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;Tabs v-model="activeTab"&gt;
  &lt;TabsList&gt;&lt;TabsTrigger value="overview"&gt;Tabel&lt;/TabsTrigger&gt;&lt;/TabsList&gt;
  &lt;TabsContent value="overview"&gt;&lt;Table&gt;...&lt;/Table&gt;&lt;/TabsContent&gt;
&lt;/Tabs&gt;</code></pre>
                    </CardContent>
                </Card>
            </section>

            <Separator />

            <!-- 4. Komponen Taktis Proyek -->
            <section class="space-y-6">
                <SectionHeader
                    title="4. Komponen Taktis (@/Components/tactical)"
                    description="Komponen khas komando SASP: TacticalFrame (3 variant × 3 accent), LiveBadge, DeptBadge, StatTile, OfficerAvatar, EmptyState, dan utilitas HUD."
                />

                <!-- TacticalFrame 3x3 Matrix -->
                <Card>
                    <CardHeader>
                        <CardTitle>TacticalFrame (3 Variant × 3 Accent)</CardTitle>
                        <CardDescription>Sudut terpotong berbasis CSS clip-path tanpa dependensi eksternal.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <template v-for="variant in frameVariants" :key="variant">
                                <TacticalFrame
                                    v-for="accent in frameAccents"
                                    :key="`${variant}-${accent}`"
                                    :variant="variant"
                                    :accent="accent"
                                >
                                    <div class="p-4">
                                        <p class="font-mono text-xs uppercase tracking-wider text-muted-foreground">
                                            variant="{{ variant }}"
                                        </p>
                                        <p class="font-heading text-lg font-bold text-foreground">
                                            accent="{{ accent }}"
                                        </p>
                                    </div>
                                </TacticalFrame>
                            </template>
                        </div>
                        <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;TacticalFrame variant="panel" accent="primary"&gt;
  &lt;div class="p-4"&gt;Konten unggulan&lt;/div&gt;
&lt;/TacticalFrame&gt;</code></pre>
                    </CardContent>
                </Card>

                <!-- LiveBadge, DeptBadge, OfficerAvatar -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>LiveBadge & DeptBadge</CardTitle>
                            <CardDescription>Indikator siaran langsung dengan jumlah penonton kompak dan identitas 4 departemen.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <LiveBadge />
                                <LiveBadge :viewers="1240" />
                                <DeptBadge dept="LSPD" />
                                <DeptBadge dept="BCSO" />
                                <DeptBadge dept="SASP" />
                                <DeptBadge dept="SAPR" />
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;LiveBadge :viewers="1240" /&gt;
&lt;DeptBadge dept="LSPD" /&gt;</code></pre>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>OfficerAvatar</CardTitle>
                            <CardDescription>Avatar perwira dengan fallback inisial dan cincin indikator LIVE.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <OfficerAvatar name="Capt Vance" size="sm" />
                                <OfficerAvatar name="Sgt Miller" size="md" :live="true" />
                                <OfficerAvatar name="Dep Reyes" size="lg" :live="true" />
                            </div>
                            <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;OfficerAvatar name="Sgt Miller" size="md" :live="true" /&gt;</code></pre>
                        </CardContent>
                    </Card>
                </div>

                <!-- StatTile & HUD Utilities -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <StatTile label="Perwira Live" :value="14" :trend="12" :icon="Radio" />
                    <StatTile label="Total Personel" :value="128" :trend="4" :icon="Users" />
                    <StatTile label="Klaim Pending" :value="3" :trend="-25" :icon="ShieldCheck" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="hud-grid hud-corners rounded-lg border border-border bg-card p-6">
                        <p class="font-mono text-xs uppercase tracking-wider text-primary">.hud-grid + .hud-corners</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Utilitas latar grid tipis dan siku HUD di keempat sudut untuk area hero atau header utama.
                        </p>
                    </div>
                    <EmptyState
                        :icon="TriangleAlert"
                        title="Tidak Ada Siaran Aktif"
                        description="Belum ada perwira yang sedang melakukan patroli live saat ini."
                    >
                        <template #action>
                            <Button variant="outline" size="sm">Muat Ulang</Button>
                        </template>
                    </EmptyState>
                </div>

                <Card class="py-4">
                    <CardContent class="px-4">
                        <pre class="overflow-x-auto rounded-md bg-background p-3 font-mono text-xs text-muted-foreground"><code>&lt;StatTile label="Perwira Live" :value="14" :trend="12" :icon="Radio" /&gt;
&lt;EmptyState title="Tidak Ada Siaran Aktif" description="..." /&gt;</code></pre>
                    </CardContent>
                </Card>
            </section>
        </PageContainer>
    </TacticalLayout>
</template>
