<script setup>
import TacticalLayout from "@/Layouts/TacticalLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    Search,
    X,
    Filter,
    ChevronsUpDown,
    CheckCircle2,
    Link2,
    ListFilter,
    LayoutGrid,
    Video,
    Scissors,
    Radio,
    User,
    Wrench,
    LifeBuoy,
    Send,
    Mic,
    Check,
    ShieldCheck,
    Clock,
} from "@lucide/vue";
import { faqsId } from "@/Data/faqs.id.js";
import { faqsEn } from "@/Data/faqs.en.js";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { PageContainer, EmptyState } from "@/Components/tactical";
import { cn } from "@/lib/utils";

const { locale } = useI18n();

defineProps({
    appVersion: {
        type: String,
        default: "2.4.0-Pro",
    },
});

// Category & Search State
const activeCategory = ref("ALL");
const searchQuery = ref("");

// Accordion State (Array of open FAQ ID strings for type="multiple")
const openFaqIds = ref(["1", "5"]);

// Copy Link Toast State
const copiedFaqCode = ref(null);

// Comprehensive IME Police SOP & FAQ Data (Multi-language Data Modules)
const faqs = computed(() => (locale.value === "en" ? faqsEn : faqsId));

// Categories configuration with Lucide icons
const categories = [
    { id: "ALL", name: "Semua Kategori", code: "ALL", icon: LayoutGrid },
    { id: "MULTIVIEW", name: "Multiview & Fitur", code: "MV", icon: Video },
    { id: "CLIPS", name: "Momen Aksi & Clip Studio", code: "CLIP", icon: Scissors },
    { id: "TAC", name: "Radio Taktis (TAC)", code: "TAC", icon: Radio },
    { id: "OFFICERS", name: "Streamer & Registrasi", code: "REG", icon: User },
    { id: "TROUBLESHOOTING", name: "Kendala Teknis", code: "SYS", icon: Wrench },
];

const quickKeywords = [
    { label: "# #imepolice", value: "#imepolice" },
    { label: "# Focus Mode", value: "Focus Mode" },
    { label: "# Klip Aksi", value: "Klip" },
    { label: "# Clip Studio", value: "Clip Studio" },
    { label: "# TAC Radio", value: "TAC" },
];

// Filtered FAQs based on Category and Search Query
const filteredFaqs = computed(() => {
    let list = faqs.value || [];

    if (activeCategory.value !== "ALL") {
        list = list.filter((item) => item.category === activeCategory.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (item) =>
                item.question.toLowerCase().includes(q) ||
                item.answer.toLowerCase().includes(q) ||
                item.code.toLowerCase().includes(q) ||
                item.categoryLabel.toLowerCase().includes(q),
        );
    }

    return list;
});

// Toggle All Accordions State
const isAllOpen = computed(() => {
    return (
        filteredFaqs.value.length > 0 &&
        filteredFaqs.value.every((f) => openFaqIds.value.includes(String(f.id)))
    );
});

const toggleAllAccordions = () => {
    if (isAllOpen.value) {
        openFaqIds.value = [];
    } else {
        openFaqIds.value = filteredFaqs.value.map((f) => String(f.id));
    }
};

// Set Search Keyword from Quick Chips
const setSearchKeyword = (keyword) => {
    searchQuery.value = keyword;
};

const clearSearch = () => {
    searchQuery.value = "";
};

// Copy Direct Deep Link URL
const copyDirectLink = (faq) => {
    const url = `${window.location.origin}${window.location.pathname}#${faq.code}`;
    navigator.clipboard.writeText(url);
    copiedFaqCode.value = faq.code;
    window.history.replaceState(null, "", `#${faq.code}`);
    setTimeout(() => {
        copiedFaqCode.value = null;
    }, 2000);
};

// Check Hash on Mount for Direct Deep Linking
onMounted(() => {
    if (window.location.hash) {
        const hash = window.location.hash.replace("#", "").toUpperCase();
        const found = faqs.value?.find((f) => f.code.toUpperCase() === hash);
        if (found) {
            openFaqIds.value = [String(found.id)];
            activeCategory.value = found.category;
            setTimeout(() => {
                const el = document.getElementById(found.code);
                if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
            }, 300);
        }
    }
});

// Dynamic Keyword Highlight Function (Semantic Primary Accent)
const highlightKeyword = (text) => {
    if (!searchQuery.value || !searchQuery.value.trim()) return text;
    const q = searchQuery.value.trim().replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${q})`, "gi");
    return text.replace(
        regex,
        '<mark class="bg-primary/20 text-primary font-bold px-1 py-0.5 rounded-sm border-b border-primary">$1</mark>',
    );
};
</script>

<template>
    <TacticalLayout>
        <Head title="SOP & FAQ — IME Police Terminal" />

        <PageContainer class="py-8 sm:py-12 space-y-12">
            <!-- Hero Header Section -->
            <section class="text-center max-w-3xl mx-auto py-4 sm:py-6 space-y-5">
                <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10">
                    DISPATCH MANUAL v{{ appVersion }} • {{ faqs.length }} {{ $t("faq.sop_count") }}
                </Badge>

                <h1 class="text-3xl sm:text-4xl md:text-5xl font-display font-bold uppercase text-foreground tracking-wider leading-tight">
                    {{ $t("faq.title") }}
                </h1>
                <p class="text-xs sm:text-sm text-muted-foreground max-w-prose mx-auto leading-relaxed">
                    {{ $t("faq.subtitle") }}
                </p>

                <!-- Search Field -->
                <div class="relative max-w-2xl mx-auto pt-4">
                    <div class="relative flex items-center">
                        <Search class="size-4 text-muted-foreground absolute left-3.5 pointer-events-none" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('faq.search_placeholder')"
                            class="pl-10 pr-10 h-11 text-sm"
                        />
                        <Button
                            v-if="searchQuery"
                            size="icon"
                            variant="ghost"
                            aria-label="Hapus pencarian"
                            class="size-8 absolute right-1.5 text-muted-foreground hover:text-foreground"
                            @click="clearSearch"
                        >
                            <X class="size-4" />
                        </Button>
                    </div>

                    <!-- Quick Search Chips -->
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs font-mono text-muted-foreground">
                        <span>Kata Kunci Cepat:</span>
                        <Button
                            v-for="kw in quickKeywords"
                            :key="kw.value"
                            size="sm"
                            variant="outline"
                            class="h-7 px-2.5 text-xs font-mono"
                            @click="setSearchKeyword(kw.value)"
                        >
                            {{ kw.label }}
                        </Button>
                    </div>
                </div>
            </section>

            <!-- 2-Column Responsive Layout: Content (Left) + Sticky Sidebar Navigation (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Main Content Area: Search Meta Bar + Accordion List (Left Column - 8 cols) -->
                <section class="lg:col-span-8 space-y-4">
                    <!-- Search Meta Indicator & Expand All Toolbar -->
                    <Card>
                        <CardContent class="px-4 py-3 flex items-center justify-between gap-3 text-xs font-mono">
                            <div class="flex items-center gap-2 text-foreground">
                                <Filter class="size-4 text-primary shrink-0" />
                                <span v-if="searchQuery">
                                    Hasil pencarian:
                                    <strong class="text-primary">{{ searchQuery }}</strong>
                                    ({{ filteredFaqs.length }} dokumen)
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Menampilkan {{ filteredFaqs.length }} dokumen SOP
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="searchQuery"
                                    size="sm"
                                    variant="ghost"
                                    class="text-primary text-xs"
                                    @click="clearSearch"
                                >
                                    Reset Filter
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="font-mono text-xs"
                                    @click="toggleAllAccordions"
                                >
                                    <ChevronsUpDown class="size-3.5 mr-1.5 text-primary" />
                                    <span>{{ isAllOpen ? "TUTUP SEMUA" : "BUKA SEMUA" }}</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Empty Search State -->
                    <Card v-if="filteredFaqs.length === 0">
                        <CardContent class="p-6">
                            <EmptyState
                                :icon="Search"
                                title="TIDAK DITEMUKAN SOP YANG COCOK"
                                description="Kami tidak menemukan dokumen SOP dengan kata kunci tersebut. Coba kata kunci lain atau reset filter."
                            >
                                <Button
                                    size="sm"
                                    class="font-mono font-bold uppercase"
                                    @click="
                                        clearSearch();
                                        activeCategory = 'ALL';
                                    "
                                >
                                    RESET FILTER PENCARIAN
                                </Button>
                            </EmptyState>
                        </CardContent>
                    </Card>

                    <!-- FAQ Accordion List -->
                    <Accordion
                        v-else
                        v-model="openFaqIds"
                        type="multiple"
                        class="space-y-3"
                    >
                        <AccordionItem
                            v-for="faq in filteredFaqs"
                            :id="faq.code"
                            :key="faq.id"
                            :value="String(faq.id)"
                            class="bg-card border border-border rounded-xl px-5 overflow-hidden"
                        >
                            <AccordionTrigger class="hover:no-underline py-4">
                                <div class="space-y-2 min-w-0 text-left pr-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Badge variant="secondary" class="font-mono uppercase text-xs">
                                            {{ faq.code }}
                                        </Badge>
                                        <span class="text-xs font-mono text-muted-foreground">
                                            {{ faq.categoryLabel }}
                                        </span>
                                    </div>
                                    <h2
                                        class="font-display text-base sm:text-lg font-bold uppercase text-foreground tracking-wide leading-snug"
                                        v-html="highlightKeyword(faq.question)"
                                    ></h2>
                                </div>
                            </AccordionTrigger>

                            <AccordionContent class="pt-3 border-t border-border text-muted-foreground text-sm leading-relaxed space-y-4">
                                <div
                                    class="prose prose-invert max-w-prose text-foreground/90 text-sm leading-relaxed"
                                    v-html="highlightKeyword(faq.answer)"
                                ></div>

                                <!-- Shareable Direct Link & Action Footer -->
                                <div class="pt-3 border-t border-border flex items-center justify-between text-xs font-mono text-muted-foreground">
                                    <span class="flex items-center gap-1.5">
                                        <CheckCircle2 class="size-3.5 text-primary" />
                                        IME Police Dispatch Document
                                    </span>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-7 px-2 text-xs font-mono"
                                        title="Copy Direct Link URL"
                                        @click.stop="copyDirectLink(faq)"
                                    >
                                        <Link2 class="size-3.5 mr-1.5 text-primary" />
                                        <span>
                                            {{
                                                copiedFaqCode === faq.code
                                                    ? "TAUTAN TERSALIN! ✓"
                                                    : "BAGIKAN TAUTAN SOP"
                                            }}
                                        </span>
                                    </Button>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </section>

                <!-- Right Column: Sticky Navigation Sidebar -->
                <aside class="lg:col-span-4 space-y-5 lg:sticky lg:top-20">
                    <!-- Category Navigation Pills Card -->
                    <Card>
                        <CardContent class="p-4 space-y-3">
                            <div class="flex items-center justify-between pb-3 border-b border-border">
                                <span class="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                                    <ListFilter class="size-4 text-primary" />
                                    KATEGORI PROSEDUR
                                </span>
                                <Badge variant="outline" class="font-mono text-primary border-primary/30 bg-primary/10 text-xs">
                                    {{
                                        activeCategory === "ALL"
                                            ? "Semua Topik"
                                            : categories.find((c) => c.id === activeCategory)?.name
                                    }}
                                </Badge>
                            </div>

                            <nav class="space-y-1.5">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    :class="
                                        cn(
                                            'w-full px-3.5 py-2.5 rounded-md text-xs font-medium transition flex items-center justify-between text-left border',
                                            activeCategory === cat.id
                                                ? 'bg-primary text-primary-foreground font-semibold border-primary'
                                                : 'bg-muted/40 text-muted-foreground hover:text-foreground hover:bg-muted border-border',
                                        )
                                    "
                                    @click="activeCategory = cat.id"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <component
                                            :is="cat.icon"
                                            :class="
                                                cn(
                                                    'size-4 shrink-0',
                                                    activeCategory === cat.id
                                                        ? 'text-primary-foreground'
                                                        : 'text-primary',
                                                )
                                            "
                                        />
                                        <span class="truncate">{{ cat.name }}</span>
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        class="font-mono text-xs shrink-0"
                                    >
                                        {{
                                            cat.id === "ALL"
                                                ? faqs.length
                                                : faqs.filter((f) => f.category === cat.id).length
                                        }}
                                    </Badge>
                                </button>
                            </nav>
                        </CardContent>
                    </Card>

                    <!-- Dispatch Support CTA Box -->
                    <Card>
                        <CardContent class="p-5 space-y-3.5">
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-md bg-muted text-primary border border-border shrink-0">
                                    <LifeBuoy class="size-5 text-primary" />
                                </div>
                                <div class="space-y-1">
                                    <h3 class="font-display font-bold text-sm uppercase text-foreground tracking-wider">
                                        BUTUH BANTUAN DISPATCHER?
                                    </h3>
                                    <p class="text-xs text-muted-foreground leading-relaxed">
                                        Ajukan pendaftaran streamer perwira baru atau laporkan kendala operasional ke komando.
                                    </p>
                                </div>
                            </div>
                            <Button as-child class="w-full font-mono font-bold uppercase tracking-wider text-xs">
                                <Link href="/feedback">
                                    <span>LAPOR DISPATCH / FEEDBACK</span>
                                    <Send class="size-3.5 ml-2" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </aside>
            </div>

            <!-- Bottom Operational Support Card (#hubungi-kami) -->
            <section id="hubungi-kami" class="pt-8 border-t border-border">
                <Card class="max-w-4xl mx-auto">
                    <CardContent class="p-6 sm:p-10 space-y-6 text-center">
                        <div class="size-12 rounded-md bg-muted text-primary border border-border flex items-center justify-center mx-auto">
                            <Mic class="size-6 text-primary" />
                        </div>

                        <div class="space-y-2">
                            <h2 class="text-xl sm:text-2xl font-display font-bold text-foreground uppercase tracking-wider">
                                BELUM MENEMUKAN SOLUSI OPERASIONAL YANG ANDA BUTUHKAN?
                            </h2>
                            <p class="text-xs sm:text-sm text-muted-foreground max-w-prose mx-auto leading-relaxed">
                                Tim Komando Dispatch dan Admin Sistem siap membantu perwira 24/7 dalam penanganan alokasi frekuensi, verifikasi channel live, atau troubleshooting bodycam.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <Button as-child class="font-mono font-bold uppercase tracking-wider text-xs">
                                <Link href="/feedback">
                                    <span>KIRIM LAPORAN / TIKET DISPATCH</span>
                                    <Send class="size-3.5 ml-2" />
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                class="font-mono font-semibold uppercase text-xs"
                                @click="
                                    clearSearch();
                                    activeCategory = 'ALL';
                                "
                            >
                                LIHAT SEMUA DOKUMEN SOP
                            </Button>
                        </div>

                        <div class="pt-5 border-t border-border flex flex-wrap items-center justify-center gap-6 text-xs font-mono text-muted-foreground">
                            <span class="flex items-center gap-2">
                                <Check class="size-4 text-primary" />
                                Dukungan Laporan Best Effort
                            </span>
                            <span class="flex items-center gap-2">
                                <ShieldCheck class="size-4 text-primary" />
                                Project Komunitas Independent
                            </span>
                            <span class="flex items-center gap-2">
                                <Clock class="size-4 text-primary" />
                                Sinkronisasi Live Otomatis
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </PageContainer>
    </TacticalLayout>
</template>
