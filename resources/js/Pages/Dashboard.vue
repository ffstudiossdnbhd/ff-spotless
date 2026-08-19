<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SundayFirstDatePicker from '../Components/SundayFirstDatePicker.vue';

const props = defineProps({
    mode: { type: String, default: 'welcome' },
    auth: { type: Object, default: () => ({ user: null, isAdmin: false }) },
    sessions: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
    currentDate: { type: String, default: '' },
    isReadOnly: { type: Boolean, default: true },
    dayUnavailable: { type: Boolean, default: false },
    uploadLimits: { type: Object, default: () => ({ maxFiles: 5, maxFileMb: 10, maxFileBytes: 10 * 1024 * 1024, maxRequestMb: 55, maxRequestBytes: 55 * 1024 * 1024 }) },
    templates: { type: [Array, Object], default: () => [] },
    weeklyTemplates: { type: [Array, Object], default: () => [] },
    monthlyTemplates: { type: [Array, Object], default: () => [] },
    collections: { type: [Array, Object], default: () => [] },
    collectionSchedules: { type: [Array, Object], default: () => [] },
    completedTasks: { type: [Array, Object], default: () => [] },
    auditLogs: { type: [Array, Object], default: () => ({ data: [], links: [] }) },
    rotationCalendar: { type: Object, default: () => ({ month: '', weeks: [] }) },
    statistics: { type: Object, default: null },
    publicHolidays: { type: Array, default: () => [] },
    publicHoliday: { type: Object, default: null },
});

const page = usePage();

// Pure Utility Functions
function collectionItems(value) {
    return Array.isArray(value) ? value : (value?.data ?? []);
}

function currentTodayString() {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Kuala_Lumpur', year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(new Date());
}

function resolveScreen() {
    if (['welcome', 'checklist', 'admin'].includes(props.mode)) return props.mode;
    return props.auth?.isAdmin ? 'admin' : 'welcome';
}

function dateOffset(value, offset) {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day, 12);
    date.setDate(date.getDate() + offset);
    return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
}

function formatTime12(timeStr) {
    if (!timeStr) return '';
    const clean = String(timeStr).trim().slice(0, 5);
    const [h, m] = clean.split(':').map(Number);
    if (isNaN(h) || isNaN(m)) return timeStr;
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 || 12;
    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
}

function formatSessionPreview(startTime, endTime) {
    if (!startTime || !endTime) return 'Isi masa mula dan tamat';
    return `${formatTime12(startTime)} - ${formatTime12(endTime)}`;
}

function formatDate(value, locale, options) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return value;
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat(locale, {
        ...options, timeZone: 'Asia/Kuala_Lumpur',
    }).format(new Date(Date.UTC(year, month - 1, day, 12)));
}

function formatTimestamp(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('en-MY', {
        dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Kuala_Lumpur',
    }).format(new Date(value));
}

function weekdayName(day) {
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][Number(day) - 1] ?? '';
}

const WEEKDAY_OPTIONS = [
    { value: 1, label: 'Monday', short: 'Mon' },
    { value: 2, label: 'Tuesday', short: 'Tue' },
    { value: 3, label: 'Wednesday', short: 'Wed' },
    { value: 4, label: 'Thursday', short: 'Thu' },
    { value: 5, label: 'Friday', short: 'Fri' },
];

function formatDaysOfWeek(days) {
    if (!days || !days.length || days.length === 5) {
        return 'Mon – Fri';
    }
    return [...days]
        .map(Number)
        .sort((a, b) => a - b)
        .map((d) => WEEKDAY_OPTIONS.find((w) => w.value === d)?.short ?? weekdayName(d))
        .filter(Boolean)
        .join(', ');
}

// Computed Properties
const activeSessions = computed(() => props.sessions.filter((session) => session.isActive));
const templates = computed(() => collectionItems(props.templates));
const weeklyTemplates = computed(() => collectionItems(props.weeklyTemplates));
const monthlyTemplates = computed(() => collectionItems(props.monthlyTemplates));

const taskEditorItems = computed(() => [...templates.value, ...weeklyTemplates.value, ...monthlyTemplates.value]
    .sort((left, right) => {
        if (Number(left.sessionId) !== Number(right.sessionId)) {
            return Number(left.sessionId) - Number(right.sessionId);
        }

        const typeOrder = { daily: 1, weekly: 2, monthly: 3 };
        if (typeOrder[left.type] !== typeOrder[right.type]) {
            return (typeOrder[left.type] || 0) - (typeOrder[right.type] || 0);
        }

        if (left.finishTime && right.finishTime) {
            return String(left.finishTime).localeCompare(String(right.finishTime));
        }

        return String(left.taskName || '').localeCompare(String(right.taskName || ''));
    }));

const taskCollections = computed(() => collectionItems(props.collections));
const manageableCollections = computed(() => taskCollections.value.filter((collection) => !collection.isDefault));
const collectionSchedules = computed(() => collectionItems(props.collectionSchedules));
const defaultCollection = computed(() => taskCollections.value.find((collection) => collection.isDefault) ?? taskCollections.value[0] ?? null);
const history = computed(() => collectionItems(props.completedTasks));
const auditLogs = computed(() => collectionItems(props.auditLogs));
const auditLinks = computed(() => props.auditLogs?.links ?? []);
const publicHolidays = computed(() => collectionItems(props.publicHolidays)
    .slice()
    .sort((left, right) => String(left.date).localeCompare(String(right.date))));

const today = computed(() => currentTodayString());
const tomorrow = computed(() => dateOffset(today.value, 1));
const isToday = computed(() => selectedDate.value === today.value);
const adminIsToday = computed(() => adminDate.value === today.value);
const locked = computed(() => props.isReadOnly || props.dayUnavailable || Boolean(props.publicHoliday) || busy.value);
const completedCount = computed(() => localTasks.value.filter((task) => task.completed).length);
const progress = computed(() => localTasks.value.length ? Math.round((completedCount.value / localTasks.value.length) * 100) : 0);

const collectionCalendarMonth = computed(() => props.rotationCalendar?.month || today.value.slice(0, 7));
const collectionCalendarWeeks = computed(() => props.rotationCalendar?.weeks ?? []);

const trendChart = Object.freeze({
    left: 54,
    right: 536,
    top: 12,
    bottom: 82,
});
const trendMax = computed(() => Math.max(1, ...(props.statistics?.trend ?? []).flatMap((row) => [
    Number(row.completed) || 0,
    Number(row.missed) || 0,
])));
const trendAxisMax = computed(() => Math.max(4, Math.ceil(trendMax.value / 4) * 4));
const trendTicks = computed(() => Array.from({ length: 5 }, (_, index) => ({
    value: trendAxisMax.value - index * (trendAxisMax.value / 4),
    y: trendChart.top + index * ((trendChart.bottom - trendChart.top) / 4),
})));

const adminTabs = [
    { key: 'statistics', label: 'Dashboard', icon: 'dashboard' },
    { key: 'history', label: 'View History', icon: 'history' },
    { key: 'collections', label: 'Rotations', icon: 'rotations' },
    { key: 'sessions', label: 'Work Sessions', icon: 'sessions' },
    { key: 'tasks', label: 'Manage Tasks', icon: 'tasks' },
    { key: 'public-holidays', label: 'Public Holiday Editor', icon: 'holiday' },
    { key: 'audit', label: 'Audit Log', icon: 'audit' },
];
const adminTabGroups = [
    adminTabs.slice(0, 2),
    adminTabs.slice(2, 6),
    adminTabs.slice(6),
];
const adminTitle = computed(() => adminTabs.find((tab) => tab.key === adminTab.value)?.label ?? 'Dashboard');
const themeToggleLabel = computed(() => theme.value === 'light'
    ? 'Light mode active. Switch to dark mode'
    : 'Dark mode active. Switch to light mode');

// State Variables (refs)
const screen = ref(resolveScreen());
const adminTab = ref('statistics');
const selectedDate = ref(props.currentDate || currentTodayString());
const adminDate = ref(props.currentDate || currentTodayString());
const localTasks = ref([]);
const theme = ref(typeof window !== 'undefined' ? (localStorage.getItem('ff-spotless-theme') || 'light') : 'light');
const notice = ref('');
const actionError = ref('');
const formErrors = ref({});
const busy = ref(false);
const mobileNavOpen = ref(false);
const confirmation = ref(null);
const adminLogin = ref('');
const evidenceTask = ref(null);
const evidenceFiles = ref([]);
const evidencePreviews = ref([]);
const completionNote = ref('');
const viewingEvidence = ref(null);
const viewingTaskDetails = ref(null);
const reopeningTask = ref(null);
const reopenReason = ref('');
const editing = ref(null);
const sessionEditing = ref(null);
let themeTransitionTimer;
let noticeDismissTimer;
const statsFrom = ref('');
const statsTo = ref('');
const expandedHistoryDescriptions = ref({});

function toggleHistoryDescription(key) {
    expandedHistoryDescriptions.value[key] = !expandedHistoryDescriptions.value[key];
}

const taskForm = ref({
    task_type: 'daily',
    task_name: '',
    description: '',
    task_session_id: '',
    days_of_week: [1, 2, 3, 4, 5],
    finish_time: '09:00',
    collection_mode: 'single',
    single_collection_id: '',
    task_collection_ids: [],
    due_weekday: 1,
});
const collectionForm = ref({ name: '' });
const collectionScheduleForm = ref({
    task_collection_id: '',
    starts_on: currentTodayString(),
    ends_on: dateOffset(currentTodayString(), 6),
});
const sessionForm = ref({ start_time: '08:00', end_time: '12:00' });
const editForm = ref({});
const sessionEditForm = ref({ start_time: '', end_time: '' });
const publicHolidayForm = ref({
    name: '',
    date: dateOffset(currentTodayString(), 1),
});
const publicHolidayEditing = ref(null);
const publicHolidayEditForm = ref({});
const taskListFilters = ref({
    collection_id: 'all',
    task_type: 'all',
    search: '',
});

watch(() => [props.mode, props.currentDate, props.tasks, props.dayUnavailable, props.auth?.isAdmin], () => {
    screen.value = resolveScreen();
    selectedDate.value = props.currentDate || currentTodayString();
    adminDate.value = props.currentDate || currentTodayString();
    localTasks.value = props.tasks.map((task) => ({ ...task }));
}, { immediate: true, deep: true });

watch(() => props.statistics, (statistics) => {
    if (!statistics) return;
    statsFrom.value = statistics.from;
    statsTo.value = statistics.to;
}, { immediate: true, deep: true });

watch(theme, (value) => {
    if (typeof document === 'undefined') return;
    localStorage.setItem('ff-spotless-theme', value);
    document.documentElement.dataset.theme = value;
    document.documentElement.style.colorScheme = value;
}, { immediate: true });

watch(activeSessions, (sessions) => {
    const firstId = sessions[0]?.id ?? '';
    if (!taskForm.value.task_session_id) {
        taskForm.value.task_session_id = firstId;
        if (sessions[0]?.endTime) {
            taskForm.value.finish_time = sessions[0].endTime.slice(0, 5);
        }
    }
}, { immediate: true });

watch(taskCollections, (collections) => {
    const firstId = collections.find((collection) => collection.isDefault)?.id ?? collections[0]?.id ?? '';
    if (!taskForm.value.single_collection_id) taskForm.value.single_collection_id = firstId;
    if (!taskForm.value.task_collection_ids.length && firstId) taskForm.value.task_collection_ids = [firstId];
    if (!collectionScheduleForm.value.task_collection_id || !manageableCollections.value.some((collection) => Number(collection.id) === Number(collectionScheduleForm.value.task_collection_id))) {
        collectionScheduleForm.value.task_collection_id = defaultSchedulableCollectionId();
    }
}, { immediate: true });

onBeforeUnmount(() => {
    clearEvidenceFiles();
    if (typeof window !== 'undefined') {
        window.clearTimeout(themeTransitionTimer);
        window.clearTimeout(noticeDismissTimer);
    }
    if (typeof document !== 'undefined') document.documentElement.classList.remove('theme-transitioning');
});

function adminIconPath(icon) {
    return {
        dashboard: 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        history: 'M12 7v5l3 2m5-2a8 8 0 1 1-2.34-5.66A8 8 0 0 1 20 12Z',
        rotations: 'M20 7v5h-5M4 17v-5h5m8.5-2.5A7 7 0 0 0 6.2 7.2L4 9.5m-1.5 5A7 7 0 0 0 13.8 16.8l2.2-2.3',
        sessions: 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        tasks: 'M9 5h6m-6 7h6m-6 7h6M5 5h.01M5 12h.01M5 19h.01',
        holiday: 'M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm3 8h.01m4 0h.01m4 0h.01m-8 4h.01m4 0h.01',
        audit: 'M6 3h9l3 3v15H6V3Zm8 0v4h4M9 11h6m-6 4h6m-6 4h4',
        logout: 'M10 17l5-5-5-5m5 5H3m18-7v14',
    }[icon] ?? '';
}

function toggleTheme() {
    if (typeof window !== 'undefined' && typeof document !== 'undefined'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const root = document.documentElement;
        root.classList.add('theme-transitioning');
        themeTransitionTimer = window.setTimeout(() => root.classList.remove('theme-transitioning'), 180);
    }

    theme.value = theme.value === 'light' ? 'dark' : 'light';
}

function sessionTasks(sessionId) {
    return localTasks.value.filter((task) => Number(task.sessionId) === Number(sessionId));
}

function defaultCollectionId() {
    return taskCollections.value?.find((collection) => collection.isDefault)?.id ?? taskCollections.value?.[0]?.id ?? '';
}

function defaultSchedulableCollectionId() {
    return manageableCollections.value?.[0]?.id ?? '';
}

function defaultTaskForm() {
    const collectionId = defaultCollectionId();
    const session = activeSessions.value?.[0];

    return {
        task_type: 'daily',
        task_name: '',
        description: '',
        task_session_id: session?.id ?? '',
        days_of_week: [1, 2, 3, 4, 5],
        finish_time: session?.endTime ? session.endTime.slice(0, 5) : '09:00',
        collection_mode: 'single',
        single_collection_id: collectionId,
        task_collection_ids: collectionId ? [collectionId] : [],
        due_weekday: 1,
    };
}

function defaultPublicHolidayForm() {
    return {
        name: '',
        date: dateOffset(currentTodayString(), 1),
    };
}

function defaultCollectionScheduleForm() {
    const startsOn = currentTodayString();

    return {
        task_collection_id: '',
        starts_on: startsOn,
        ends_on: dateOffset(startsOn, 6),
    };
}

function monthStart(value) {
    if (!/^\d{4}-\d{2}$/.test(value || '')) return `${currentTodayString().slice(0, 7)}-01`;
    return `${value}-01`;
}

function shiftMonth(value, offset) {
    const base = monthStart(value);
    const [year, month] = base.split('-').map(Number);
    const date = new Date(year, month - 1, 1, 12);
    date.setMonth(date.getMonth() + offset);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function collectionCalendarMonthLabel(value) {
    const [year, month] = monthStart(value).split('-').map(Number);
    return new Intl.DateTimeFormat('en-MY', {
        month: 'long', year: 'numeric', timeZone: 'Asia/Kuala_Lumpur',
    }).format(new Date(Date.UTC(year, month - 1, 1, 12)));
}

function prevCollectionCalendarMonth() {
    openRotationCalendar(shiftMonth(collectionCalendarMonth.value, -1));
}

function nextCollectionCalendarMonth() {
    openRotationCalendar(shiftMonth(collectionCalendarMonth.value, 1));
}

function goToCollectionCalendarToday() {
    openRotationCalendar(today.value.slice(0, 7));
}

function openRotationCalendar(month) {
    if (!month || month === collectionCalendarMonth.value) return;

    openAdmin(adminDate.value, { rotation_month: month });
}

function collectionDisplayName(collection) {
    if (!collection) return '';

    return collection.isDefault ? 'Always active' : collection.name;
}

function collectionDisplayNameById(collectionId, fallback = '') {
    const collection = taskCollections.value.find((item) => Number(item.id) === Number(collectionId));

    return collection ? collectionDisplayName(collection) : fallback;
}

function collectionTone(collectionId) {
    const palette = [
        'border-sky-400/40 bg-sky-400/10 text-sky-300',
        'border-emerald-400/40 bg-emerald-400/10 text-emerald-300',
        'border-violet-400/40 bg-violet-400/10 text-violet-300',
        'border-amber-400/40 bg-amber-400/10 text-amber-300',
        'border-rose-400/40 bg-rose-400/10 text-rose-300',
    ];
    const index = taskCollections.value.findIndex((collection) => Number(collection.id) === Number(collectionId));

    return palette[index >= 0 ? index % palette.length : 0];
}

function collectionDayTone(collectionId) {
    const palette = [
        'border-sky-400/60 bg-sky-500/20 hover:border-sky-300/80 hover:bg-sky-500/25',
        'border-emerald-400/60 bg-emerald-500/20 hover:border-emerald-300/80 hover:bg-emerald-500/25',
        'border-violet-400/60 bg-violet-500/20 hover:border-violet-300/80 hover:bg-violet-500/25',
        'border-amber-400/60 bg-amber-500/20 hover:border-amber-300/80 hover:bg-amber-500/25',
        'border-rose-400/60 bg-rose-500/20 hover:border-rose-300/80 hover:bg-rose-500/25',
    ];
    const index = taskCollections.value.findIndex((collection) => Number(collection.id) === Number(collectionId));

    return palette[index >= 0 ? index % palette.length : 0];
}

function shortCollectionName(name) {
    if (!name) return '';
    return name.length > 18 ? `${name.slice(0, 18)}...` : name;
}

function sundayIndex(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return 0;
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day, 12).getDay();
}

function collectionCalendarDayTone(day, rotation) {
    if (day.isWeekend) {
        return day.inMonth ? 'border-zinc-700 bg-zinc-900/70' : 'border-zinc-800 bg-zinc-950/60';
    }

    return rotation
        ? collectionDayTone(rotation.id)
        : (day.inMonth ? 'border-zinc-700 bg-zinc-900' : 'border-zinc-800 bg-zinc-950/60');
}

function collectionCalendarWeekLabel(week) {
    return `Week ${week.calendarWeek}`;
}

function collectionCalendarBandDays(week) {
    return week.days.filter((day) => !day.isWeekend);
}

function collectionCalendarBandHasInMonthDay(week) {
    return collectionCalendarBandDays(week).some((day) => day.inMonth);
}

function collectionCalendarBandTone(rotation) {
    const lightPalette = [
        { frame: 'border-sky-500/80 text-sky-950', fill: 'bg-sky-500/35' },
        { frame: 'border-emerald-500/80 text-emerald-950', fill: 'bg-emerald-500/35' },
        { frame: 'border-violet-500/80 text-violet-950', fill: 'bg-violet-500/35' },
        { frame: 'border-amber-500/80 text-amber-950', fill: 'bg-amber-500/35' },
        { frame: 'border-rose-500/80 text-rose-950', fill: 'bg-rose-500/35' },
    ];
    const darkPalette = [
        { frame: 'border-sky-400/80 text-sky-100', fill: 'bg-sky-500/40' },
        { frame: 'border-emerald-400/80 text-emerald-100', fill: 'bg-emerald-500/40' },
        { frame: 'border-violet-400/80 text-violet-100', fill: 'bg-violet-500/40' },
        { frame: 'border-amber-400/80 text-amber-100', fill: 'bg-amber-500/40' },
        { frame: 'border-rose-400/80 text-rose-100', fill: 'bg-rose-500/40' },
    ];
    const index = taskCollections.value.findIndex((collection) => Number(collection.id) === Number(rotation?.id));
    const palette = theme.value === 'light' ? lightPalette : darkPalette;

    return palette[index >= 0 ? index % palette.length : 0];
}

function auditActorTone(actorType) {
    return actorType === 'cleaner'
        ? 'border border-amber-400/40 bg-amber-500/10 text-amber-300'
        : 'border border-sky-400/40 bg-sky-500/10 text-sky-300';
}

function historyFor(sessionId) {
    return history.value.filter((item) => Number(item.sessionId) === Number(sessionId));
}

function taskEditorItemsFor(sessionId) {
    return taskEditorItems.value
        .filter((item) => Number(item.sessionId) === Number(sessionId))
        .filter((item) => taskListFilters.value.task_type === 'all' || item.type === taskListFilters.value.task_type)
        .filter(matchesTaskCollectionFilter)
        .filter(matchesTaskSearchFilter);
}

function matchesTaskSearchFilter(item) {
    const q = taskListFilters.value.search?.trim().toLowerCase();
    if (!q) return true;
    return (item.taskName || '').toLowerCase().includes(q);
}

function matchesTaskCollectionFilter(item) {
    if (taskListFilters.value.collection_id === 'all') return true;
    if (item.appliesToAllCollections) return true;

    return (item.collectionIds ?? []).map(Number).includes(Number(taskListFilters.value.collection_id));
}

function filteredTaskEditorItemsFor(sessionId) {
    return taskEditorItemsFor(sessionId);
}

function taskCollectionSummary(item) {
    if (item.appliesToAllCollections) {
        return 'All rotations';
    }

    return item.collectionNames?.length
        ? item.collectionNames.map((name, index) => collectionDisplayNameById(item.collectionIds?.[index], name)).join(', ')
        : 'No rotations';
}

function taskCollectionPills(item) {
    if (item.appliesToAllCollections) {
        return [{ key: 'all', name: 'All rotations', collectionId: null }];
    }

    if (!item.collectionNames?.length) {
        return [{ key: 'none', name: 'No rotations', collectionId: null }];
    }

    return item.collectionNames.map((name, index) => {
        const collectionId = item.collectionIds?.[index] ?? null;

        return {
            key: `${collectionId ?? name}`,
            name: collectionDisplayNameById(collectionId, name),
            collectionId,
        };
    });
}

function taskTypeLabel(type) {
    if (type === 'monthly') return 'Bulanan';
    if (type === 'weekly') return 'Mingguan';
    return 'Harian';
}

function toggleAllDays(form) {
    if (form.days_of_week?.length === 5) {
        form.days_of_week = [];
    } else {
        form.days_of_week = [1, 2, 3, 4, 5];
    }
}

function normalizeTaskPayload(form) {
    const collectionIds = form.collection_mode === 'all'
        ? []
        : form.collection_mode === 'multiple'
            ? [...new Set((form.task_collection_ids ?? []).map(Number).filter(Boolean))]
            : (form.single_collection_id ? [Number(form.single_collection_id)] : []);

    const finishTime = form.finish_time ? (form.finish_time.length === 5 ? `${form.finish_time}:00` : form.finish_time) : '';

    const payload = {
        task_name: form.task_name,
        description: form.description?.trim() || null,
        task_session_id: form.task_session_id,
        applies_to_all_collections: form.collection_mode === 'all',
        task_collection_ids: collectionIds,
        due_weekday: form.due_weekday,
        finish_time: finishTime,
    };

    if (form.task_type === 'daily' || form.days_of_week !== undefined) {
        payload.days_of_week = (form.days_of_week ?? [1, 2, 3, 4, 5]).map(Number);
    }

    return payload;
}

function sessionTone(index) {
    return ['text-amber-300', 'text-sky-300', 'text-violet-300', 'text-emerald-300', 'text-rose-300'][index % 5];
}

function displayDate(value) {
    return formatDate(value, 'ms-MY', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

function displayChecklistWeekday(value) {
    return formatDate(value, 'ms-MY', { weekday: 'long' });
}

function displayChecklistDate(value) {
    return formatDate(value, 'ms-MY', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}

function displayAdminDate(value) {
    return formatDate(value, 'en-MY', {
        weekday: 'long', day: 'numeric', month: 'short', year: 'numeric',
    });
}

function displayDateInput(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return '';
    return `${value.slice(8, 10)}/${value.slice(5, 7)}/${value.slice(0, 4)}`;
}

function setNotice(message) {
    clearNoticeDismissTimer();
    actionError.value = '';
    notice.value = message;

    if (typeof window !== 'undefined') {
        noticeDismissTimer = window.setTimeout(() => {
            notice.value = '';
            noticeDismissTimer = undefined;
        }, 4500);
    }
}

function closeStatus() {
    clearNoticeDismissTimer();
    notice.value = '';
    actionError.value = '';
}

function clearNoticeDismissTimer() {
    if (typeof window !== 'undefined' && noticeDismissTimer) {
        window.clearTimeout(noticeDismissTimer);
    }

    noticeDismissTimer = undefined;
}

function dismissNoticeOnInteraction() {
    if (notice.value) closeStatus();
}

function clearErrors(scope) {
    if (!scope) {
        formErrors.value = {};
        return;
    }

    const next = { ...formErrors.value };
    delete next[scope];
    formErrors.value = next;
}

function errorFor(scope, field) {
    const error = formErrors.value[scope]?.[field];
    return Array.isArray(error) ? error[0] : (error ?? '');
}

function errorId(scope, field) {
    return `${scope}-${field}-error`;
}

function validationAttrs(scope, field) {
    const error = errorFor(scope, field);
    return error ? { 'aria-invalid': 'true', 'aria-describedby': errorId(scope, field) } : {};
}

function fail(errors, fallback, scope = 'global') {
    const normalized = Object.entries(errors ?? {}).reduce((result, [field, messages]) => ({
        ...result,
        [field]: Array.isArray(messages) ? messages : [messages],
    }), {});
    const message = Object.values(normalized).flat()[0] ?? fallback;
    formErrors.value = { ...formErrors.value, [scope]: normalized };
    clearNoticeDismissTimer();
    actionError.value = message;
    notice.value = '';
    nextTick(() => document.querySelector('[data-error-summary]')?.focus());
}

function inertiaOptions(message, fallback, onSuccess = null, errorScope = 'global') {
    return {
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            busy.value = true;
            clearErrors(errorScope);
        },
        onSuccess: () => {
            setNotice(message);
            onSuccess?.();
        },
        onError: (errors) => fail(errors, fallback, errorScope),
        onFinish: () => busy.value = false,
    };
}

function selectAdminTab(tab) {
    adminTab.value = tab.key;
    mobileNavOpen.value = false;
}

function requestConfirmation({ title, description, confirmLabel, action }) {
    confirmation.value = { title, description, confirmLabel, action };
}

function closeConfirmation() {
    if (busy.value) return;
    confirmation.value = null;
}

function confirmAction() {
    const action = confirmation.value?.action;
    confirmation.value = null;
    action?.();
}

function loginAdmin() {
    router.post('/admin/login', { password: adminLogin.value }, {
        preserveScroll: true,
        onStart: () => {
            busy.value = true;
            clearErrors('login');
        },
        onSuccess: () => {
            setNotice('Admin access opened.');
            adminLogin.value = '';
            screen.value = 'admin';
        },
        onError: (errors) => fail(errors, 'Admin password was not accepted.', 'login'),
        onFinish: () => {
            busy.value = false;
        },
    });
}

function openAdminLogin() {
    screen.value = 'admin-login';
    nextTick(() => window.setTimeout(() => document.getElementById('admin-password')?.focus(), 0));
}

function logoutAdmin() {
    router.post('/admin/logout', {}, {
        preserveScroll: true,
        onStart: () => busy.value = true,
        onSuccess: () => {
            screen.value = 'welcome';
        },
        onFinish: () => {
            busy.value = false;
            screen.value = 'welcome';
        },
    });
}

function openWelcome() {
    router.get('/', {}, {
        preserveScroll: true,
        onStart: () => busy.value = true,
        onSuccess: () => {
            screen.value = 'welcome';
        },
        onFinish: () => busy.value = false,
    });
}

function openChecklist(date = null) {
    router.get('/checklist', date ? { date } : {}, {
        preserveScroll: true,
        onStart: () => busy.value = true,
        onSuccess: () => {
            screen.value = 'checklist';
        },
        onError: (errors) => fail(errors, 'Senarai semak tidak dapat dibuka.'),
        onFinish: () => busy.value = false,
    });
}

function openAdmin(date = null, options = {}) {
    const data = {
        date: date || adminDate.value,
        stats_from: statsFrom.value,
        stats_to: statsTo.value,
        rotation_month: collectionCalendarMonth.value,
        ...options,
    };

    Object.keys(data).forEach((key) => {
        if (!data[key]) delete data[key];
    });

    router.get('/admin', data, {
        preserveScroll: true,
        onStart: () => busy.value = true,
        onSuccess: () => {
            screen.value = 'admin';
        },
        onFinish: () => busy.value = false,
    });
}

function toggleAvailability() {
    if (props.publicHoliday) return;

    router.post('/checklist/availability', {
        date: selectedDate.value,
        is_unavailable: !props.dayUnavailable,
    }, inertiaOptions(
        props.dayUnavailable ? 'Hari ini tersedia semula.' : 'Hari ini ditandakan MC/tidak tersedia.',
        'Status ketersediaan tidak dapat dikemas kini.',
    ));
}

function openEvidence(task) {
    if (locked.value || task.completed) return;
    evidenceTask.value = task;
    evidenceFiles.value = [];
    evidencePreviews.value = [];
    completionNote.value = '';
}

function openTaskDetails(task) {
    viewingTaskDetails.value = task;
}

function closeTaskDetails() {
    viewingTaskDetails.value = null;
}

function selectEvidence(event) {
    const selected = Array.from(event.target.files ?? []);
    const files = [...evidenceFiles.value, ...selected];
    if (files.length > Number(props.uploadLimits.maxFiles)) {
        fail({ photos: [`Pilih maksimum ${props.uploadLimits.maxFiles} foto bagi satu penghantaran.`] }, 'Foto bukti tidak sah.', 'evidence');
        event.target.value = '';
        return;
    }
    const maximumBytes = Number(props.uploadLimits.maxFileBytes) || Number(props.uploadLimits.maxFileMb) * 1024 * 1024;
    const oversized = files.find((file) => file.size > maximumBytes);
    if (oversized) {
        fail({ photos: [`${oversized.name} melebihi had ${props.uploadLimits.maxFileMb} MB setiap foto.`] }, 'Foto bukti tidak sah.', 'evidence');
        event.target.value = '';
        return;
    }
    const maxRequestBytes = Number(props.uploadLimits.maxRequestBytes) || Number(props.uploadLimits.maxRequestMb) * 1024 * 1024;
    const totalBytes = files.reduce((total, file) => total + file.size, 0);
    if (totalBytes > maxRequestBytes) {
        fail({ photos: [`Jumlah saiz foto melebihi had penghantaran ${props.uploadLimits.maxRequestMb} MB.`] }, 'Foto bukti tidak sah.', 'evidence');
        event.target.value = '';
        return;
    }
    clearErrors('evidence');
    evidenceFiles.value = files;
    evidencePreviews.value.push(...selected.map((file) => URL.createObjectURL(file)));
    event.target.value = '';
}

function removeEvidence(index) {
    URL.revokeObjectURL(evidencePreviews.value[index]);
    evidenceFiles.value.splice(index, 1);
    evidencePreviews.value.splice(index, 1);
}

function clearEvidenceFiles() {
    evidencePreviews.value.forEach((url) => URL.revokeObjectURL(url));
    evidencePreviews.value = [];
    evidenceFiles.value = [];
}

function closeEvidence(force = false) {
    if (busy.value && force !== true) return;
    clearEvidenceFiles();
    completionNote.value = '';
    evidenceTask.value = null;
}

function completeTask() {
    if (!evidenceTask.value || !evidenceFiles.value.length) {
        fail({ photos: ['Pilih sekurang-kurangnya satu foto bukti.'] }, 'Foto bukti diperlukan.', 'evidence');
        return;
    }
    const form = new FormData();
    form.append('date', selectedDate.value);
    form.append('note', completionNote.value);
    evidenceFiles.value.forEach((file) => form.append('photos[]', file));
    const task = evidenceTask.value;
    router.post(`/tasks/${task.type}/${task.id}/complete`, form, {
        ...inertiaOptions('Tugasan selesai dengan bukti foto.', 'Tugasan tidak dapat diselesaikan.', () => closeEvidence(true), 'evidence'),
        forceFormData: true,
    });
}

function openReopen(task) {
    reopeningTask.value = task;
    reopenReason.value = '';
}

function closeReopen(force = false) {
    if (busy.value && force !== true) return;
    reopeningTask.value = null;
    reopenReason.value = '';
}

function reopenTask() {
    if (!reopeningTask.value) return;
    const reason = reopenReason.value.trim();
    if (!reason) {
        fail({ reason: ['A reopen reason is required.'] }, 'A reopen reason is required.', 'reopen');
        return;
    }

    const task = reopeningTask.value;
    router.patch(`/admin/tasks/${task.type}/${task.id}/reopen`, { reason }, inertiaOptions(
        'Task reopened. The prior evidence is preserved in the audit log.',
        'Task could not be reopened.',
        () => closeReopen(true),
        'reopen',
    ));
}

function createTask() {
    const type = taskForm.value.task_type;
    const base = type === 'monthly'
        ? '/admin/monthly-templates'
        : (type === 'weekly' ? '/admin/weekly-templates' : '/admin/templates');

    const label = type === 'monthly' ? 'Monthly' : (type === 'weekly' ? 'Weekly' : 'Daily');

    router.post(base, normalizeTaskPayload(taskForm.value), inertiaOptions(
        `${label} task added.`,
        `${label} task could not be added.`,
        () => taskForm.value = defaultTaskForm(),
        'task',
    ));
}

function createCollection() {
    router.post('/admin/collections', collectionForm.value, inertiaOptions(
        'Rotation added.', 'Rotation could not be added.', () => collectionForm.value.name = '', 'collection',
    ));
}

function createPublicHoliday() {
    router.post('/admin/public-holidays', publicHolidayForm.value, inertiaOptions(
        'Public holiday added.',
        'Public holiday could not be added.',
        () => publicHolidayForm.value = defaultPublicHolidayForm(),
        'publicHoliday',
    ));
}

function editPublicHoliday(holiday) {
    if (!holiday.isEditable) return;

    publicHolidayEditing.value = holiday;
    publicHolidayEditForm.value = {
        name: holiday.name,
        date: holiday.date,
    };
}

function closePublicHolidayEdit(force = false) {
    if (busy.value && force !== true) return;

    publicHolidayEditing.value = null;
    publicHolidayEditForm.value = {};
}

function savePublicHoliday() {
    if (!publicHolidayEditing.value?.isEditable) return;

    router.patch(`/admin/public-holidays/${publicHolidayEditing.value.id}`, publicHolidayEditForm.value, inertiaOptions(
        'Public holiday updated.',
        'Public holiday could not be updated.',
        () => closePublicHolidayEdit(true),
        'publicHolidayEdit',
    ));
}

function deletePublicHoliday(holiday) {
    if (!holiday.isEditable) return;

    requestConfirmation({
        title: 'Delete public holiday?',
        description: `The office will reopen on ${displayAdminDate(holiday.date)} and future tasks may be restored for that date.`,
        confirmLabel: 'Delete public holiday',
        action: () => router.delete(`/admin/public-holidays/${holiday.id}`, inertiaOptions(
            'Public holiday deleted.', 'Public holiday could not be deleted.', null, 'publicHoliday',
        )),
    });
}

function deleteCollection(collection) {
    if (collection.isDefault) return;
    requestConfirmation({
        title: 'Delete rotation permanently?',
        description: `Delete “${collection.name}” permanently. This is only possible when no task or schedule still uses it.`,
        confirmLabel: 'Delete rotation',
        action: () => router.delete(`/admin/collections/${collection.id}`, inertiaOptions(
            'Rotation deleted.', 'Rotation could not be deleted.', null, 'collection',
        )),
    });
}

function openEdit(kind, item) {
    editing.value = { kind, item };
    editForm.value = {
        task_type: item.type,
        task_name: item.taskName,
        description: item.description || '',
        task_session_id: item.sessionId,
        days_of_week: item.daysOfWeek ? [...item.daysOfWeek] : [1, 2, 3, 4, 5],
        finish_time: item.finishTime ? item.finishTime.slice(0, 5) : '',
        collection_mode: item.appliesToAllCollections ? 'all' : ((item.collectionIds?.length ?? 0) > 1 ? 'multiple' : 'single'),
        single_collection_id: item.collectionIds?.[0] ?? defaultCollectionId(),
        task_collection_ids: item.collectionIds?.length ? [...item.collectionIds] : (defaultCollectionId() ? [defaultCollectionId()] : []),
        due_weekday: item.dueWeekday ?? 1,
    };
}

function saveEdit() {
    const type = editForm.value.task_type;
    const base = type === 'monthly'
        ? '/admin/monthly-templates'
        : (type === 'weekly' ? '/admin/weekly-templates' : '/admin/templates');

    router.patch(`${base}/${editing.value.item.id}`, normalizeTaskPayload(editForm.value), inertiaOptions(
        'Template updated.', 'Template could not be updated.', () => editing.value = null, 'taskEdit',
    ));
}

function deleteTemplate(kind, item) {
    const base = kind === 'monthly'
        ? '/admin/monthly-templates'
        : (kind === 'weekly' ? '/admin/weekly-templates' : '/admin/templates');

    requestConfirmation({
        title: 'Archive task?',
        description: `“${item.taskName}” will stop appearing on new checklists. Existing history and completed records will be kept.`,
        confirmLabel: 'Archive task',
        action: () => router.delete(`${base}/${item.id}`, inertiaOptions(
            'Template archived.', 'Template could not be archived.', null, 'task',
        )),
    });
}

function createSession() {
    router.post('/admin/sessions', sessionForm.value, inertiaOptions(
        'Session added.', 'Session could not be added.', () => sessionForm.value = { start_time: '08:00', end_time: '12:00' }, 'session',
    ));
}

function editSession(session) {
    sessionEditing.value = session;
    sessionEditForm.value = {
        start_time: session.startTime ? session.startTime.slice(0, 5) : '08:00',
        end_time: session.endTime ? session.endTime.slice(0, 5) : '12:00',
    };
}

function saveSession() {
    router.patch(`/admin/sessions/${sessionEditing.value.id}`, sessionEditForm.value, inertiaOptions(
        'Work session updated.', 'Work session could not be updated.', () => sessionEditing.value = null, 'sessionEdit',
    ));
}

function archiveSession(session) {
    requestConfirmation({
        title: 'Archive work session?',
        description: `“${session.name}” will be hidden from new tasks. Archive is only available after every active task has been moved to another session.`,
        confirmLabel: 'Archive session',
        action: () => router.delete(`/admin/sessions/${session.id}`, inertiaOptions(
            'Session archived.', 'Session could not be archived.', null, 'session',
        )),
    });
}

function statsPreset(days) {
    adminTab.value = 'statistics';
    statsFrom.value = dateOffset(today.value, -(days - 1));
    statsTo.value = today.value;
    openAdmin(adminDate.value, { stats_from: statsFrom.value, stats_to: statsTo.value });
}

function chooseStatsFrom(date) {
    statsFrom.value = date;
    refreshStats();
}

function chooseStatsTo(date) {
    statsTo.value = date;
    refreshStats();
}

function refreshStats() {
    if (!statsFrom.value || !statsTo.value || statsFrom.value > statsTo.value) return;

    openAdmin(adminDate.value, { stats_from: statsFrom.value, stats_to: statsTo.value });
}

function trendLinePoints(metric) {
    const trend = props.statistics?.trend ?? [];

    return trend.map((row, index) => {
        const x = trendPointX(index, trend.length);
        const value = Math.max(0, Number(row[metric]) || 0);
        const y = trendPointY(value);
        return `${x.toFixed(2)},${y.toFixed(2)}`;
    }).join(' ');
}

function trendPointX(index, length) {
    if (length <= 1) return (trendChart.left + trendChart.right) / 2;
    return trendChart.left + (index / (length - 1)) * (trendChart.right - trendChart.left);
}

function trendPointY(value) {
    const height = trendChart.bottom - trendChart.top;
    return trendChart.bottom - (Math.max(0, Number(value) || 0) / trendAxisMax.value) * height;
}

function trendRangeLabel() {
    if (!props.statistics?.from || !props.statistics?.to) return '';

    return `${displayDateInput(props.statistics.from)} - ${displayDateInput(props.statistics.to)}`;
}

function auditActionLabel(action) {
    return {
        'admin.login_succeeded': 'Admin logged in',
        'admin.login_failed': 'Admin login failed',
        'admin.logout': 'Admin logged out',
        'task.completed': 'Task completed',
        'task.reopened': 'Task reopened',
        'session.created': 'Work session added',
        'session.updated': 'Work session updated',
        'session.archived': 'Work session archived',
        'task_template.created': 'Task added',
        'task_template.updated': 'Task updated',
        'task_template.archived': 'Task archived',
        'rotation.created': 'Rotation added',
        'rotation.deleted': 'Rotation deleted',
        'public_holiday.created': 'Public holiday added',
        'public_holiday.updated': 'Public holiday updated',
        'public_holiday.deleted': 'Public holiday deleted',
        'availability.marked_unavailable': 'Marked unavailable',
        'availability.marked_available': 'Marked available',
    }[action] ?? action.replaceAll('.', ' ');
}

function auditDetails(audit) {
    const details = audit.metadata ?? {};
    return [details.task_name, details.name, details.task_date ?? details.date, details.reason]
        .filter(Boolean)
        .join(' - ');
}

function openAuditPage(url) {
    if (!url) return;
    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

function auditLinkLabel(label) {
    return String(label ?? '')
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace(/<[^>]*>/g, '');
}

function chooseAdminDate(date) {
    if (date) openAdmin(date);
}
</script>

<template>
    <div :class="theme === 'light' ? 'theme-light' : 'theme-dark'" class="min-h-screen bg-[#121212] text-zinc-100" @pointerdown.capture="dismissNoticeOnInteraction" @keydown.capture="dismissNoticeOnInteraction">
        <div v-if="notice || actionError" data-error-summary tabindex="-1" class="fixed inset-x-4 top-4 z-[80] mx-auto flex max-w-lg items-start gap-3 rounded-xl border px-4 py-3 text-sm font-semibold shadow-2xl" :class="actionError ? 'border-rose-500/40 bg-rose-950 text-rose-100' : 'border-emerald-500/40 bg-emerald-950 text-emerald-100'" :role="actionError ? 'alert' : 'status'" aria-live="polite">
            <span class="min-w-0 flex-1">{{ actionError || notice }}</span>
            <button type="button" class="small-button shrink-0" aria-label="Dismiss notification" @click="closeStatus">Dismiss</button>
        </div>

        <main v-if="screen === 'welcome'" class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12 text-center">
            <div class="mx-auto mb-7 flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-[#ED4264] to-[#FFEDBC] text-3xl font-black text-zinc-950 shadow-xl shadow-rose-950/40">FF</div>
            <h1 class="text-3xl font-black tracking-tight">FF Spotless</h1>
            <p class="mt-3 text-sm leading-relaxed text-zinc-400">Senarai semak pembersihan harian, mingguan, dan bulanan mengikut jadual sesi kerja.</p>
            <button class="mt-8 h-14 rounded-2xl bg-gradient-to-r from-[#ED4264] to-[#FFEDBC] font-black text-zinc-950" @click="openChecklist()">Buka Senarai</button>
            <button class="mt-3 h-12 rounded-2xl border border-zinc-700 text-sm font-bold text-zinc-300" @click="openAdminLogin">Admin</button>
        </main>

        <main v-else-if="screen === 'admin-login'" class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
            <button class="mb-8 w-fit text-sm text-zinc-400" @click="screen = 'welcome'">&lt; Back</button>
            <h1 class="text-center text-2xl font-black">Admin Access</h1>
            <form class="mt-7 space-y-3" @submit.prevent="loginAdmin">
                <label class="form-label text-center" for="admin-password">Admin password</label>
                <input id="admin-password" v-model="adminLogin" required type="password" autocomplete="current-password" class="h-14 w-full rounded-2xl border border-zinc-700 bg-zinc-900 px-4 text-center tracking-widest outline-none focus:border-[#ED4264]" placeholder="********" v-bind="validationAttrs('login', 'password')">
                <p v-if="errorFor('login', 'password')" :id="errorId('login', 'password')" class="field-error">{{ errorFor('login', 'password') }}</p>
                <button :disabled="busy" class="h-14 w-full rounded-2xl bg-gradient-to-r from-[#ED4264] to-[#FFEDBC] font-black text-zinc-950 disabled:opacity-50">Log in</button>
            </form>
        </main>

        <main v-else-if="screen === 'checklist'" class="mx-auto min-h-screen max-w-3xl">
            <header class="sticky top-0 z-20 border-b border-zinc-800 bg-[#121212]/95 px-5 py-4 backdrop-blur">
                <div class="flex items-center justify-between">
                    <button type="button" class="cleaner-logout text-sm font-bold" @click="openWelcome">&lt; Log Keluar</button>
                    <button type="button" class="theme-toggle rounded-lg border border-zinc-700" :aria-label="themeToggleLabel" :title="themeToggleLabel" @click="toggleTheme">
                        <svg v-if="theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke-linecap="round"></path>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z" stroke-linejoin="round"></path>
                        </svg>
                        <span>{{ theme === 'light' ? 'Light' : 'Dark' }}</span>
                    </button>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <button :disabled="busy" class="h-10 w-10 rounded-xl border border-zinc-700" @click="openChecklist(dateOffset(selectedDate, -1))">&lt;</button>
                    <button class="min-w-0 flex-1 text-center" @click="openChecklist()">
                        <span class="checklist-date-weekday block text-sm text-white">{{ displayChecklistWeekday(selectedDate) }}</span>
                        <span class="checklist-date-value mt-0.5 block text-xs text-zinc-400">{{ displayChecklistDate(selectedDate) }}</span>
                        <span v-if="!isToday" class="text-xs font-bold uppercase text-[#ED4264]">Kembali ke hari ini</span>
                    </button>
                    <button :disabled="busy" class="h-10 w-10 rounded-xl border border-zinc-700" @click="openChecklist(dateOffset(selectedDate, 1))">&gt;</button>
                </div>
            </header>

            <section class="px-5 pt-5">
                <div v-if="isReadOnly" class="rounded-xl border border-zinc-700 bg-zinc-900/40 p-3 text-xs text-zinc-400">Tarikh lampau dan masa hadapan adalah baca sahaja.</div>
                <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-zinc-800"><div class="h-full bg-gradient-to-r from-[#ED4264] to-[#FFEDBC]" :style="{ width: `${progress}%` }"></div></div>
                <div class="mt-2 flex justify-between text-xs text-zinc-400"><span>{{ completedCount }} daripada {{ localTasks.length }} selesai</span><span>{{ progress }}%</span></div>
            </section>

            <section class="space-y-7 px-5 py-6">
                <div v-if="!localTasks.length" class="rounded-2xl border border-dashed border-zinc-700 p-10 text-center text-sm text-zinc-500">Tiada tugasan untuk tarikh ini.</div>
                <section v-for="(session, sessionIndex) in sessions" :key="session.id" v-show="sessionTasks(session.id).length">
                    <header class="mb-3 flex items-center justify-between">
                        <div class="flex items-baseline gap-2">
                            <h2 class="font-black uppercase tracking-wider" :class="sessionTone(sessionIndex)">{{ session.name }}</h2>
                        </div>
                        <span class="rounded-full border border-zinc-700 px-2.5 py-1 text-xs font-bold text-zinc-400">{{ sessionTasks(session.id).length }} tugasan</span>
                    </header>
                    <div class="space-y-2">
                        <article v-for="task in sessionTasks(session.id)" :key="task.key" class="flex items-center gap-3 rounded-2xl border p-3.5" :class="(dayUnavailable || publicHoliday) && !task.completed ? 'border-zinc-800 bg-zinc-900/40 opacity-60' : 'border-zinc-700 bg-zinc-900'">
                            <button
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2"
                                :class="task.completed ? 'border-[#ED4264] bg-[#ED4264] text-white' : (locked || dayUnavailable || publicHoliday) ? 'border-zinc-700 cursor-not-allowed opacity-50' : 'border-zinc-500 hover:border-[#ED4264]'"
                                :disabled="locked || task.completed"
                                :aria-label="task.completed ? 'Tugasan selesai' : `Tandakan ${task.text} selesai`"
                                @click="openEvidence(task)"
                            >
                                <span v-if="task.completed" class="text-xs font-black">&#10003;</span>
                            </button>

                            <div class="min-w-0 flex-1 cursor-pointer" @click="openTaskDetails(task)">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="block text-sm font-semibold" :class="task.completed ? 'text-zinc-500 line-through' : (dayUnavailable || publicHoliday) ? 'text-zinc-600' : 'text-zinc-100'">{{ task.text }}</span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-bold text-zinc-400">
                                    <span v-if="task.timeSpanFormatted || task.timeSpan" class="rounded-md border border-zinc-700 bg-zinc-800/80 px-2 py-0.5 text-[11px] font-semibold text-zinc-300">{{ task.timeSpanFormatted || task.timeSpan }}</span>
                                    <span v-if="task.isMonthly" class="rounded-md border border-purple-500/40 bg-purple-500/10 px-2 py-0.5 text-[11px] font-semibold text-purple-300">Bulanan</span>
                                    <span v-else-if="task.isWeekly" class="rounded-md border border-sky-500/40 bg-sky-500/10 px-2 py-0.5 text-[11px] font-semibold text-sky-300">Mingguan</span>
                                    <span v-if="task.postponedCount" class="text-[11px] text-amber-400">Ditunda {{ task.postponedCount }}x</span>
                                    <span v-if="task.description" class="inline-flex items-center gap-1 text-[11px] text-zinc-400">
                                        <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                                        Butiran
                                    </span>
                                </div>
                            </div>

                            <button
                                v-if="task.description"
                                type="button"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-700 text-zinc-400 transition hover:border-zinc-500 hover:text-zinc-200"
                                aria-label="Lihat penerangan tugasan"
                                title="Lihat penerangan tugasan"
                                @click="openTaskDetails(task)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                            </button>
                        </article>
                    </div>
                </section>
            </section>

            <section class="px-5 pb-6">
                <div v-if="publicHoliday" class="rounded-2xl border border-sky-400/40 bg-sky-400/10 p-4">
                    <strong class="block text-sm text-sky-200">Office closed: {{ publicHoliday.name }}</strong>
                    <span class="mt-1 block text-xs leading-relaxed text-zinc-300">No cleaning tasks are scheduled for this custom public holiday.</span>
                </div>
                <label v-else class="flex items-start gap-3 rounded-2xl border p-4" :class="dayUnavailable ? 'border-rose-500/40 bg-rose-500/10' : 'border-zinc-700 bg-zinc-900/50'">
                    <input type="checkbox" class="mt-1 h-5 w-5 accent-[#ED4264]" :checked="dayUnavailable" :disabled="!isToday || busy" @change="toggleAvailability">
                    <span>
                        <strong class="block text-sm">MC / tidak tersedia hari ini</strong>
                        <span class="mt-1 block text-xs leading-relaxed text-zinc-400">Mengunci tugasan harian dan memindahkan tugasan mingguan/bulanan yang perlu dibuat hari ini.</span>
                    </span>
                </label>
            </section>
        </main>

        <main v-else-if="screen === 'admin'" class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
            <aside class="sticky top-0 hidden h-screen flex-col border-r border-zinc-800 bg-[#121212]/95 px-5 py-6 lg:flex">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#ED4264]">Admin</p>
                    <h1 class="mt-1 text-xl font-black">FF Spotless</h1>
                </div>
                <nav class="mt-8 space-y-4" aria-label="Admin navigation">
                    <template v-for="(group, groupIndex) in adminTabGroups" :key="`admin-group-${groupIndex}`">
                        <div v-if="groupIndex" class="border-t border-zinc-800"></div>
                        <div class="space-y-2">
                            <button v-for="tab in group" :key="tab.key" class="admin-tab flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left text-sm font-bold" :class="adminTab === tab.key ? 'admin-tab-active border-[#ED4264]/40 bg-[#ED4264]/10 text-rose-200' : 'border-transparent text-zinc-400 hover:border-zinc-700 hover:text-zinc-100'" :aria-current="adminTab === tab.key ? 'page' : undefined" @click="selectAdminTab(tab)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0"><path :d="adminIconPath(tab.icon)" stroke-linecap="round" stroke-linejoin="round"></path></svg><span>{{ tab.label }}</span></button>
                        </div>
                    </template>
                </nav>
                <div class="mt-auto flex items-center gap-2">
                    <button class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-zinc-700 px-3 text-xs font-bold text-rose-300" @click="logoutAdmin"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path :d="adminIconPath('logout')" stroke-linecap="round" stroke-linejoin="round"></path></svg><span>Log out</span></button>
                    <button type="button" class="theme-toggle shrink-0 rounded-lg border border-zinc-700" :aria-label="themeToggleLabel" :title="themeToggleLabel" @click="toggleTheme">
                        <svg v-if="theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke-linecap="round"></path>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z" stroke-linejoin="round"></path>
                        </svg>
                        <span>{{ theme === 'light' ? 'Light' : 'Dark' }}</span>
                    </button>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-zinc-800 bg-[#121212]/95 px-5 py-4 backdrop-blur lg:hidden">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-900/60 text-zinc-200 transition hover:border-zinc-500 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ED4264]"
                            :aria-expanded="mobileNavOpen"
                            aria-controls="admin-mobile-drawer"
                            aria-label="Open navigation menu"
                            @click="mobileNavOpen = true"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#ED4264]">Admin</p>
                            <h1 class="text-lg font-black">FF Spotless</h1>
                        </div>
                    </div>
                </header>

                <div class="lg:hidden">
                    <Transition name="drawer-backdrop">
                        <div
                            v-if="mobileNavOpen"
                            class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs"
                            aria-hidden="true"
                            @click="mobileNavOpen = false"
                        ></div>
                    </Transition>

                    <Transition name="drawer-panel">
                        <div
                            v-if="mobileNavOpen"
                            id="admin-mobile-drawer"
                            class="fixed inset-y-0 left-0 z-50 flex h-full w-80 max-w-[85vw] flex-col border-r border-zinc-800 bg-[#121212] px-5 py-6 shadow-2xl overflow-y-auto"
                            role="dialog"
                            aria-modal="true"
                            aria-label="Admin navigation drawer"
                        >
                            <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#ED4264]">Admin</p>
                                    <p class="text-xl font-black">FF Spotless</p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-700 text-zinc-400 hover:border-zinc-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#ED4264]/50"
                                    aria-label="Close navigation menu"
                                    @click="mobileNavOpen = false"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>

                            <nav class="mt-6 flex-1 space-y-4" aria-label="Admin mobile navigation">
                                <template v-for="(group, groupIndex) in adminTabGroups" :key="`mobile-admin-group-${groupIndex}`">
                                    <div v-if="groupIndex" class="border-t border-zinc-800"></div>
                                    <div class="space-y-2">
                                        <button
                                            v-for="tab in group"
                                            :key="tab.key"
                                            class="admin-tab flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left text-sm font-bold"
                                            :class="adminTab === tab.key ? 'admin-tab-active border-[#ED4264]/40 bg-[#ED4264]/10 text-rose-200' : 'border-transparent text-zinc-400 hover:border-zinc-700 hover:text-zinc-100'"
                                            :aria-current="adminTab === tab.key ? 'page' : undefined"
                                            @click="selectAdminTab(tab)"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0">
                                                <path :d="adminIconPath(tab.icon)" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                            <span>{{ tab.label }}</span>
                                        </button>
                                    </div>
                                </template>
                            </nav>

                            <div class="mt-6 flex items-center gap-2 border-t border-zinc-800 pt-4">
                                <button class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-zinc-700 px-3 text-xs font-bold text-rose-300" @click="logoutAdmin">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path :d="adminIconPath('logout')" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    <span>Log out</span>
                                </button>
                                <button type="button" class="theme-toggle shrink-0 rounded-lg border border-zinc-700" :aria-label="themeToggleLabel" :title="themeToggleLabel" @click="toggleTheme">
                                    <svg v-if="theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke-linecap="round"></path>
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z" stroke-linejoin="round"></path>
                                    </svg>
                                    <span>{{ theme === 'light' ? 'Light' : 'Dark' }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <section class="mx-auto max-w-6xl px-5 py-6">
                    <div class="mb-6 hidden items-end justify-between lg:flex">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.2em] text-zinc-500">Admin Dashboard</p>
                            <h2 class="mt-1 text-2xl font-black">{{ adminTitle }}</h2>
                        </div>
                    </div>

                    <details v-if="adminTab === 'tasks'" class="group mb-4 rounded-xl border border-violet-400/30 bg-violet-400/5">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-3 py-2 marker:hidden">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-violet-300">Manage Tasks help</p>
                                <p class="truncate text-xs font-bold text-zinc-200">How task timing and schedule types work</p>
                            </div>
                            <span class="rounded-full border border-violet-400/30 bg-violet-400/10 px-2 py-0.5 text-xs font-black uppercase text-violet-200 transition group-open:bg-violet-400/20">Open</span>
                        </summary>
                        <div class="grid gap-1.5 px-3 pb-3 text-[11px] text-zinc-300 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">Daily, Weekly, or Monthly</p>
                                <p class="mt-0.5 text-zinc-400">Daily repeats each active day. Weekly appears on the chosen weekday. Monthly appears on the last Friday of each month.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">Finish time & Dynamic Start</p>
                                <p class="mt-0.5 text-zinc-400">Set the target finish time. The start time is calculated automatically from previous task's finish time or session start time.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">Task Details / Description</p>
                                <p class="mt-0.5 text-zinc-400">Add detailed instructions for cleaners to review in the task info modal.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">Rotation allocation</p>
                                <p class="mt-0.5 text-zinc-400">Assign tasks to one rotation, multiple rotations, or all rotations.</p>
                            </div>
                        </div>
                    </details>

                    <details v-if="adminTab === 'collections'" class="group mb-4 rounded-xl border border-sky-400/30 bg-sky-400/5">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-3 py-2 marker:hidden">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-300">Rotation help</p>
                                <p class="truncate text-xs font-bold text-zinc-200">How the weekly cycle works</p>
                            </div>
                            <span class="rounded-full border border-sky-400/30 bg-sky-400/10 px-2 py-0.5 text-xs font-black uppercase text-sky-200 transition group-open:bg-sky-400/20">Open</span>
                        </summary>
                        <div class="grid gap-1.5 px-3 pb-3 text-[11px] text-zinc-300 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">1. Always active</p>
                                <p class="mt-0.5 text-zinc-400">Use this for tasks that appear on every working day.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">2. Rotations</p>
                                <p class="mt-0.5 text-zinc-400">Create rotations such as Heavy Duty or Light Duty; each owns one week.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">3. Weekly cycle</p>
                                <p class="mt-0.5 text-zinc-400">The order repeats automatically from Sunday through Saturday.</p>
                            </div>
                            <div class="rounded-lg border border-zinc-700 bg-zinc-950/50 p-2.5">
                                <p class="font-black text-zinc-100">4. Task rotation choice</p>
                                <p class="mt-0.5 text-zinc-400">Single uses one rotation, Multiple uses selected rotations, All appears no matter what is active.</p>
                            </div>
                        </div>
                    </details>

                    <div v-if="adminTab === 'statistics' && statistics" class="space-y-6">
                        <section class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-4 sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div><h2 class="font-black">Date range</h2><p class="mt-1 text-xs text-zinc-500">Choose a quick period or set a custom inclusive range.</p></div>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="days in [7,30,90]" :key="days" type="button" class="small-button" :class="statsFrom === dateOffset(today, -(days - 1)) && statsTo === today ? 'border-rose-400 bg-rose-400/10 text-rose-200' : ''" @click="statsPreset(days)">{{ days }} days</button>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <SundayFirstDatePicker :model-value="statsFrom" label="From" :max="statsTo || today" :theme="theme" @update:model-value="chooseStatsFrom" />
                                <SundayFirstDatePicker :model-value="statsTo" label="To" :min="statsFrom" :max="today" :theme="theme" @update:model-value="chooseStatsTo" />
                            </div>
                        </section>
                        <p class="text-xs text-zinc-500">Accurate statistics are tracked from {{ displayAdminDate(statistics.trackingStart) }}.</p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div v-for="card in [['Completed', statistics.overview.completed], ['Missed', statistics.overview.missed], ['Total tasks', statistics.overview.totalTasks], ['Completion rate', statistics.overview.completionRate + '%']]" :key="card[0]" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-4"><p class="text-xs font-bold uppercase text-zinc-500">{{ card[0] }}</p><p class="mt-2 text-2xl font-black">{{ card[1] }}</p></div>
                        </div>
                        <div class="rounded-2xl border border-zinc-700 bg-zinc-900 p-5">
                            <div class="flex flex-wrap items-baseline justify-between gap-2"><h2 class="font-black">Daily Trend</h2><p class="text-xs text-zinc-500">{{ trendRangeLabel() }}</p></div>
                            <div class="mt-4 flex items-center gap-4 text-xs font-bold"><span class="inline-flex items-center gap-2 text-sky-300"><i class="h-2.5 w-2.5 rounded-full bg-sky-400"></i>Completed</span><span class="inline-flex items-center gap-2 text-rose-300"><i class="h-2.5 w-2.5 bg-rose-400"></i>Missed</span></div>
                            <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-800 bg-zinc-950/5 px-2 py-3">
                                <svg class="block h-auto min-w-[35rem] w-full" viewBox="0 0 560 116" role="img" aria-labelledby="daily-trend-title daily-trend-description">
                                    <title id="daily-trend-title">Daily trend</title><desc id="daily-trend-description">Completed and missed tasks for working days from {{ trendRangeLabel() }}.</desc>
                                    <g v-for="tick in trendTicks" :key="tick.value"><line :x1="trendChart.left" :x2="trendChart.right" :y1="tick.y" :y2="tick.y" stroke="currentColor" stroke-width="0.5" stroke-opacity="0.7" class="text-zinc-700"></line><text x="42" :y="tick.y + 2.5" text-anchor="end" fill="currentColor" class="text-zinc-500" font-size="8">{{ tick.value }}</text></g>
                                    <line :x1="trendChart.left" :x2="trendChart.right" :y1="trendChart.bottom" :y2="trendChart.bottom" stroke="currentColor" stroke-width="1" class="text-zinc-600"></line>
                                    <polyline :points="trendLinePoints('completed')" fill="none" stroke="#60a5fa" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></polyline><polyline :points="trendLinePoints('missed')" fill="none" stroke="#fb7185" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                    <g v-for="(row, index) in statistics.trend" :key="row.date"><circle :cx="trendPointX(index, statistics.trend.length)" :cy="trendPointY(row.completed)" r="1.9" fill="#60a5fa"><title>{{ `${displayAdminDate(row.date)}: ${row.completed} completed` }}</title></circle><rect :x="trendPointX(index, statistics.trend.length) - 1.8" :y="trendPointY(row.missed) - 1.8" width="3.6" height="3.6" fill="#fb7185"><title>{{ `${displayAdminDate(row.date)}: ${row.missed} missed` }}</title></rect></g>
                                    <text :x="trendChart.left" y="104" text-anchor="start" fill="currentColor" class="text-zinc-400" font-size="8">{{ displayDateInput(statistics.from) }}</text><text :x="trendChart.right" y="104" text-anchor="end" fill="currentColor" class="text-zinc-400" font-size="8">{{ displayDateInput(statistics.to) }}</text>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="adminTab === 'history'" class="space-y-6">
                        <div class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-4 sm:p-5">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div class="flex items-end gap-2"><button class="history-nav-button" aria-label="Previous day" @click="openAdmin(dateOffset(adminDate, -1))"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m12.5 4.5-5.5 5.5 5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button><SundayFirstDatePicker :model-value="adminDate" label="History date" :theme="theme" @update:model-value="chooseAdminDate" /><button class="history-nav-button" aria-label="Next day" @click="openAdmin(dateOffset(adminDate, 1))"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.5 5.5 5.5-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                                <div class="flex flex-wrap items-center gap-3"><button v-if="!adminIsToday" class="small-button" @click="openAdmin(today)">Back to today</button><div class="history-selected-date"><span>Viewing</span><strong>{{ displayAdminDate(adminDate) }}</strong></div></div>
                            </div>
                        </div>
                        <section v-for="(session, index) in sessions" :key="session.id" v-show="historyFor(session.id).length">
                            <header class="mb-3 flex items-center justify-between gap-3">
                                <h2 class="font-black uppercase" :class="sessionTone(index)">{{ session.name }}</h2>
                                <span class="rounded-full border border-zinc-700 px-2.5 py-1 text-xs font-bold text-zinc-400">{{ historyFor(session.id).length }} record{{ historyFor(session.id).length === 1 ? '' : 's' }}</span>
                            </header>
                            <div class="grid gap-3 md:grid-cols-2">
                                <article v-for="entry in historyFor(session.id)" :key="entry.key" class="flex h-full min-h-[140px] flex-col rounded-xl border bg-zinc-900 p-4 transition duration-150" :class="entry.evidence?.length ? 'border-zinc-700 hover:-translate-y-0.5 hover:border-sky-400/70 hover:bg-sky-400/5 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-sky-400' : 'border-zinc-700 opacity-75'">
                                    <button class="flex h-full w-full flex-1 flex-col justify-between text-left disabled:cursor-default" :class="entry.evidence?.length ? 'cursor-pointer' : ''" :disabled="!entry.evidence?.length" @click="viewingEvidence = entry">
                                        <div class="w-full">
                                            <div class="flex items-start justify-between gap-3">
                                                <strong class="min-w-0 flex-1 text-sm text-zinc-100">{{ entry.text }}</strong>
                                                <span class="shrink-0 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-black uppercase" :class="entry.status === 'completed' ? 'bg-emerald-500/10 text-emerald-300' : entry.status === 'missed' ? 'bg-rose-500/10 text-rose-300' : 'bg-zinc-800 text-zinc-400'">{{ entry.status === 'completed' ? 'Completed' : entry.status === 'missed' ? 'Missed' : 'Pending' }}</span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-zinc-400">
                                                <span v-if="entry.timeSpanFormatted || entry.timeSpan" class="rounded-md border border-zinc-700 bg-zinc-800 px-2 py-0.5 text-[11px] text-zinc-300">{{ entry.timeSpanFormatted || entry.timeSpan }}</span>
                                                <span v-if="entry.type === 'monthly'" class="text-purple-300">Monthly</span>
                                                <span v-else-if="entry.type === 'weekly'" class="text-sky-300">Weekly (due {{ displayAdminDate(entry.originalDueDate) }})</span>
                                                <span v-else class="text-zinc-400">Daily</span>
                                            </div>
                                            <div v-if="entry.description" class="mt-2 text-xs text-zinc-400">
                                                <p :class="expandedHistoryDescriptions[entry.key] ? '' : 'line-clamp-1'">{{ entry.description }}</p>
                                                <span
                                                    role="button"
                                                    tabindex="0"
                                                    class="mt-1 inline-block cursor-pointer font-bold text-rose-400 hover:underline"
                                                    @click.stop="toggleHistoryDescription(entry.key)"
                                                    @keydown.enter.stop="toggleHistoryDescription(entry.key)"
                                                    @keydown.space.stop.prevent="toggleHistoryDescription(entry.key)"
                                                >
                                                    {{ expandedHistoryDescriptions[entry.key] ? 'less' : '...more' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-3 border-t border-zinc-800/80 pt-2 text-xs">
                                            <p v-if="entry.isCompleted" class="text-zinc-500">{{ formatTimestamp(entry.completedAt) }} - {{ entry.evidence.length }} photo{{ entry.evidence.length === 1 ? '' : 's' }}</p>
                                            <p v-if="entry.completionNote" class="mt-1 text-zinc-300 font-medium">Note: {{ entry.completionNote }}</p>
                                            <p v-if="entry.evidence?.length" class="mt-2 inline-flex items-center gap-1 font-bold text-sky-300">View proof photo{{ entry.evidence.length === 1 ? '' : 's' }} <span aria-hidden="true">-&gt;</span></p>
                                        </div>
                                    </button>
                                </article>
                            </div>
                        </section>
                        <p v-if="!history.length" class="rounded-2xl border border-dashed border-zinc-700 p-10 text-center text-sm text-zinc-500">No records for this date.</p>
                    </div>

                    <div v-else-if="adminTab === 'public-holidays'" class="grid gap-6 lg:grid-cols-[360px_1fr]">
                        <form class="space-y-3 rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5" @submit.prevent="createPublicHoliday">
                            <h2 class="font-black">Add Public Holiday</h2>
                            <p class="text-sm leading-relaxed text-zinc-400">Add a custom office-closure date. No tasks or statistics will be recorded for that day.</p>
                            <label class="form-label" for="public-holiday-name">Holiday name</label>
                            <input id="public-holiday-name" v-model.trim="publicHolidayForm.name" required maxlength="100" class="field" placeholder="For example, Company annual leave" v-bind="validationAttrs('publicHoliday', 'name')">
                            <p v-if="errorFor('publicHoliday', 'name')" :id="errorId('publicHoliday', 'name')" class="field-error">{{ errorFor('publicHoliday', 'name') }}</p>
                            <label class="form-label" for="public-holiday-date">Closure date</label>
                            <input id="public-holiday-date" v-model="publicHolidayForm.date" required type="date" :min="tomorrow" class="field" v-bind="validationAttrs('publicHoliday', 'date')">
                            <p v-if="errorFor('publicHoliday', 'date')" :id="errorId('publicHoliday', 'date')" class="field-error">{{ errorFor('publicHoliday', 'date') }}</p>
                            <p class="text-xs leading-relaxed text-zinc-500">Dates must be future weekdays. A closure cannot be changed once its date begins.</p>
                            <button :disabled="busy" class="primary-button">Add public holiday</button>
                        </form>

                        <section class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-black">Scheduled Public Holidays</h2>
                                    <p class="mt-1 text-sm text-zinc-400">Custom office closures are shown in date order.</p>
                                </div>
                                <span class="rounded-full border border-zinc-700 px-2.5 py-1 text-xs font-black text-zinc-400">{{ publicHolidays.length }} scheduled</span>
                            </div>
                            <p v-if="!publicHolidays.length" class="mt-5 rounded-xl border border-dashed border-zinc-700 p-8 text-center text-sm text-zinc-500">No custom public holidays are scheduled.</p>
                            <div v-else class="mt-5 space-y-2">
                                <article v-for="holiday in publicHolidays" :key="holiday.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-700 bg-zinc-900 p-4">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-semibold text-zinc-100">{{ holiday.name }}</h3>
                                        <p class="mt-1 text-xs text-zinc-500">{{ displayAdminDate(holiday.date) }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span v-if="!holiday.isEditable" class="rounded-full border border-zinc-700 px-2 py-1 text-xs font-black uppercase text-zinc-500">Locked</span>
                                        <template v-else>
                                            <button type="button" class="small-button inline-flex h-9 w-9 items-center justify-center p-0" :disabled="busy" aria-label="Edit public holiday" title="Edit public holiday" @click="editPublicHoliday(holiday)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m4 16.5-.7 4.2 4.2-.7L18.8 8.7l-3.5-3.5L4 16.5Z" stroke-linejoin="round"></path><path d="m13.8 6.7 3.5 3.5" stroke-linecap="round"></path></svg></button>
                                            <button type="button" class="small-button inline-flex h-9 w-9 items-center justify-center p-0 text-rose-400 transition hover:border-rose-400/60 hover:bg-rose-400/10 hover:text-rose-300" :disabled="busy" aria-label="Delete public holiday" title="Delete public holiday" @click="deletePublicHoliday(holiday)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
                                        </template>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <div v-else-if="adminTab === 'audit'" class="space-y-4">
                        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5"><h2 class="font-black">Activity audit log</h2><p class="mt-1 text-sm text-zinc-400">Authentication, task, rotation, work-session, public-holiday, availability, and ordering changes are retained here. Sensitive credentials and file paths are never recorded.</p></div>
                        <p v-if="!auditLogs.length" class="rounded-2xl border border-dashed border-zinc-700 p-10 text-center text-sm text-zinc-500">No audit activity has been recorded yet.</p>
                        <article v-for="audit in auditLogs" :key="audit.id" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-black">{{ auditActionLabel(audit.action) }}</h3><p v-if="auditDetails(audit)" class="mt-1 text-sm text-zinc-300">{{ auditDetails(audit) }}</p></div><span class="rounded-full px-2 py-1 text-xs font-black uppercase" :class="auditActorTone(audit.actorType)">{{ audit.actorType }}</span></div><p class="mt-3 text-xs text-zinc-500">{{ audit.actorLabel }} - {{ formatTimestamp(audit.occurredAt) }}</p></article>
                        <nav v-if="auditLinks.length > 3" class="flex flex-wrap gap-2" aria-label="Audit log pages"><button v-for="link in auditLinks" :key="link.label" type="button" class="small-button" :class="link.active ? 'border-rose-400 bg-rose-400/10 text-rose-200' : ''" :disabled="!link.url" @click="openAuditPage(link.url)">{{ auditLinkLabel(link.label) }}</button></nav>
                    </div>

                    <div v-else-if="adminTab === 'collections'" class="grid gap-6 lg:grid-cols-[320px_1fr]">
                        <aside class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-zinc-500">Rotations</p>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-400">Each custom rotation owns one Sunday–Saturday week. Rotations repeat in the order they are added.</p>
                            <form class="mt-5 space-y-2" @submit.prevent="createCollection">
                                <label class="form-label" for="collection-name">Rotation name</label>
                                <input id="collection-name" v-model.trim="collectionForm.name" required maxlength="100" class="field !py-2 text-sm" placeholder="For example, Heavy Duty" v-bind="validationAttrs('collection', 'name')">
                                <p v-if="errorFor('collection', 'name')" :id="errorId('collection', 'name')" class="field-error">{{ errorFor('collection', 'name') }}</p>
                                <button class="primary-button !py-2 text-sm">Add rotation</button>
                            </form>
                            <article v-if="defaultCollection" class="mt-5 flex items-center justify-between gap-3 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-zinc-200">{{ collectionDisplayName(defaultCollection) }}</p>
                                    <p class="text-xs text-zinc-500">Fallback on working days</p>
                                </div>
                                <span class="rounded-full border border-zinc-700 px-2 py-0.5 text-xs font-black uppercase text-zinc-400">System</span>
                            </article>
                            <p v-if="!manageableCollections.length" class="mt-4 rounded-xl border border-dashed border-zinc-700 p-3 text-xs text-zinc-500">No custom rotations yet. The fallback rotation is active on weekdays.</p>
                            <div v-else class="mt-4 space-y-2">
                                <article v-for="(collection, index) in manageableCollections" :key="`manage-${collection.id}`" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-zinc-200">Week {{ index + 1 }}: {{ collectionDisplayName(collection) }}</p>
                                    </div>
                                    <button class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0 text-rose-400 transition hover:border-rose-400/60 hover:bg-rose-400/10 hover:text-rose-300" aria-label="Delete rotation" title="Delete rotation" @click="deleteCollection(collection)">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </button>
                                </article>
                            </div>
                        </aside>
                        <section class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-4 sm:p-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-zinc-500">Rotation calendar</p>
                                <p class="mt-1 text-xs text-zinc-400">The calendar starts Sunday and shows the active rotation for every week.</p>
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <button type="button" class="calendar-nav-button" aria-label="Previous month" @click="prevCollectionCalendarMonth"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m12.5 4.5-5.5 5.5 5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
                                    <span class="min-w-32 text-center text-xs font-black text-zinc-200">{{ collectionCalendarMonthLabel(collectionCalendarMonth) }}</span>
                                    <button type="button" class="calendar-nav-button" aria-label="Next month" @click="nextCollectionCalendarMonth"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.5 5.5 5.5-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
                                    <button type="button" class="calendar-nav-button !w-auto px-4 text-xs font-bold" @click="goToCollectionCalendarToday">Today</button>
                                </div>
                            </div>
                            <div class="rotation-calendar-scroll mt-5">
                                <div class="rotation-calendar" aria-label="Rotation calendar">
                                    <div class="rotation-calendar-header text-center text-xs font-black uppercase tracking-[0.12em] text-zinc-500"><span aria-hidden="true"></span><span v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">{{ day }}</span></div>
                                    <div v-for="week in collectionCalendarWeeks" :key="week.weekStart" class="rotation-calendar-week">
                                        <span class="rotation-calendar-week-label">{{ collectionCalendarWeekLabel(week) }}</span>
                                        <div v-for="day in week.days" :key="day.date" class="rotation-calendar-cell rounded-md border p-1.5" :class="[collectionCalendarDayTone(day, week.rotation), !day.inMonth ? 'opacity-55' : '']" :style="{ gridColumn: sundayIndex(day.date) + 2 }"><div class="flex items-start justify-between gap-2"><span class="text-xs font-black" :class="day.isToday ? 'text-red-600' : day.inMonth ? 'text-zinc-100' : 'text-zinc-500'">{{ day.dayNumber }}</span><span v-if="day.isToday" class="text-[9px] font-black uppercase text-red-600">Today</span></div></div>
                                        <span v-if="week.rotation" class="rotation-calendar-band" :class="[collectionCalendarBandTone(week.rotation).frame, !collectionCalendarBandHasInMonthDay(week) ? 'opacity-55' : '']">
                                            <span class="rotation-calendar-band__segments" aria-hidden="true">
                                                <span v-for="day in collectionCalendarBandDays(week)" :key="`rotation-band-${day.date}`" class="rotation-calendar-band__segment" :class="[collectionCalendarBandTone(week.rotation).fill, collectionCalendarBandHasInMonthDay(week) && !day.inMonth ? 'opacity-55' : '']"></span>
                                            </span>
                                            <span class="rotation-calendar-band__label">Rotation: {{ shortCollectionName(collectionDisplayName(week.rotation)) }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div v-else-if="adminTab === 'sessions'" class="grid gap-6 lg:grid-cols-[360px_1fr]">
                        <form class="h-fit space-y-3 rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5" @submit.prevent="createSession">
                            <h2 class="font-black">Add Work Session</h2>
                            <p class="text-xs text-zinc-400">Set the session start and end time. The system will automatically name and order sessions by time.</p>

                            <div class="grid grid-cols-2 gap-2">
                                <label class="form-label" for="session-start">
                                    Start time
                                    <input id="session-start" v-model="sessionForm.start_time" required type="time" class="field" v-bind="validationAttrs('session', 'start_time')">
                                </label>
                                <label class="form-label" for="session-end">
                                    End time
                                    <input id="session-end" v-model="sessionForm.end_time" required type="time" class="field" v-bind="validationAttrs('session', 'end_time')">
                                </label>
                            </div>
                            <p v-if="errorFor('session', 'start_time')" :id="errorId('session', 'start_time')" class="field-error">{{ errorFor('session', 'start_time') }}</p>
                            <p v-if="errorFor('session', 'end_time')" :id="errorId('session', 'end_time')" class="field-error">{{ errorFor('session', 'end_time') }}</p>

                            <div class="rounded-xl border border-zinc-700 bg-zinc-950 p-3 text-xs">
                                <span class="text-zinc-500 font-bold uppercase tracking-wider block">Preview</span>
                                <strong class="text-sm text-zinc-200 mt-1 block">{{ formatSessionPreview(sessionForm.start_time, sessionForm.end_time) }}</strong>
                            </div>

                            <button class="primary-button">Add work session</button>
                        </form>
                        <div class="space-y-2">
                            <article v-for="(session, index) in activeSessions" :key="session.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-700 bg-zinc-900 p-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-7 text-center font-black text-zinc-500">{{ index + 1 }}</span>
                                    <div>
                                        <strong class="text-base text-zinc-100">{{ session.name }}</strong>
                                        <p class="text-xs text-zinc-500 mt-0.5">{{ sessionTasks(session.id).length }} task{{ sessionTasks(session.id).length === 1 ? '' : 's' }} assigned</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="small-button inline-flex h-9 w-9 items-center justify-center p-0" aria-label="Edit work session" title="Edit work session" @click="editSession(session)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m4 16.5-.7 4.2 4.2-.7L18.8 8.7l-3.5-3.5L4 16.5Z" stroke-linejoin="round"></path><path d="m13.8 6.7 3.5 3.5" stroke-linecap="round"></path></svg></button>
                                    <button class="small-button text-rose-300" @click="archiveSession(session)">Archive</button>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-else-if="adminTab === 'tasks'" class="grid gap-6 lg:grid-cols-[360px_1fr]">
                        <form class="space-y-3 rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5" @submit.prevent="createTask">
                            <h2 class="font-black">Add New Task</h2>
                            <label class="form-label" for="task-name">Task name</label>
                            <input id="task-name" v-model.trim="taskForm.task_name" required maxlength="255" class="field" placeholder="For example, Mop the lobby" v-bind="validationAttrs('task', 'task_name')">
                            <p v-if="errorFor('task', 'task_name')" :id="errorId('task', 'task_name')" class="field-error">{{ errorFor('task', 'task_name') }}</p>

                            <label class="form-label" for="task-description">
                                Description / details <span class="font-normal text-zinc-500">(optional)</span>
                            </label>
                            <textarea id="task-description" v-model.trim="taskForm.description" rows="3" class="field h-auto py-2" placeholder="Specific guidelines, chemicals, or equipment to use..."></textarea>

                            <label class="form-label">Task schedule type
                                <select v-model="taskForm.task_type" class="field" v-bind="validationAttrs('task', 'task_type')">
                                    <option value="daily">Daily (Every active day)</option>
                                    <option value="weekly">Weekly (Specific day of week)</option>
                                    <option value="monthly">Monthly (Last Friday of month)</option>
                                </select>
                            </label>

                            <label class="form-label">Work session
                                <select v-model="taskForm.task_session_id" required class="field" v-bind="validationAttrs('task', 'task_session_id')">
                                    <option v-for="session in activeSessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                            </label>
                            <p v-if="errorFor('task', 'task_session_id')" :id="errorId('task', 'task_session_id')" class="field-error">{{ errorFor('task', 'task_session_id') }}</p>

                            <label class="form-label" for="task-finish-time">
                                Finish / due time
                                <input id="task-finish-time" v-model="taskForm.finish_time" required type="time" class="field" v-bind="validationAttrs('task', 'finish_time')">
                            </label>
                            <p v-if="errorFor('task', 'finish_time')" :id="errorId('task', 'finish_time')" class="field-error">{{ errorFor('task', 'finish_time') }}</p>

                            <label v-if="taskForm.task_type === 'weekly'" class="form-label">Weekly due day
                                <select v-model.number="taskForm.due_weekday" class="field" v-bind="validationAttrs('task', 'due_weekday')">
                                    <option v-for="day in 5" :key="day" :value="day">{{ weekdayName(day) }}</option>
                                </select>
                            </label>
                            <p v-if="errorFor('task', 'due_weekday')" :id="errorId('task', 'due_weekday')" class="field-error">{{ errorFor('task', 'due_weekday') }}</p>

                            <div v-if="taskForm.task_type === 'daily'" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500">Days of week</p>
                                    <button type="button" class="text-xs font-semibold text-rose-400 hover:underline" @click="toggleAllDays(taskForm)">
                                        {{ taskForm.days_of_week?.length === 5 ? 'Deselect all' : 'Select all' }}
                                    </button>
                                </div>
                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    <label
                                        v-for="day in WEEKDAY_OPTIONS"
                                        :key="day.value"
                                        class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-colors"
                                        :class="taskForm.days_of_week?.includes(day.value) ? 'border-rose-500/60 bg-rose-500/10 text-rose-200' : 'border-zinc-700 bg-zinc-800/60 text-zinc-400 hover:border-zinc-600'"
                                    >
                                        <input
                                            v-model="taskForm.days_of_week"
                                            type="checkbox"
                                            :value="day.value"
                                            class="accent-rose-500"
                                        >
                                        <span>{{ day.label }}</span>
                                    </label>
                                </div>
                                <p v-if="errorFor('task', 'days_of_week')" :id="errorId('task', 'days_of_week')" class="field-error mt-2">{{ errorFor('task', 'days_of_week') }}</p>
                            </div>

                            <label class="form-label">Where this task appears<select v-model="taskForm.collection_mode" class="field"><option value="single">One rotation</option><option value="multiple">Selected rotations</option><option value="all">All rotations</option></select></label>
                            <label v-if="taskForm.collection_mode === 'single'" class="form-label">Rotation<select v-model="taskForm.single_collection_id" required class="field" v-bind="validationAttrs('task', 'task_collection_ids')"><option v-for="collection in taskCollections" :key="collection.id" :value="collection.id">{{ collectionDisplayName(collection) }}</option></select></label>
                            <div v-else-if="taskForm.collection_mode === 'multiple'" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500">Choose rotations</p>
                                <label v-for="collection in taskCollections" :key="collection.id" class="mt-3 flex items-center gap-3 text-sm text-zinc-300"><input v-model="taskForm.task_collection_ids" type="checkbox" :value="collection.id"><span>{{ collectionDisplayName(collection) }}</span></label>
                            </div>
                            <p v-if="errorFor('task', 'task_collection_ids')" :id="errorId('task', 'task_collection_ids')" class="field-error">{{ errorFor('task', 'task_collection_ids') }}</p>
                            <button :disabled="busy" class="primary-button">Add task</button>
                        </form>
                        <div class="space-y-6">
                            <div class="rounded-2xl border border-zinc-700 bg-zinc-900/50 p-5">
                                <div>
                                    <h2 class="font-black">Find Tasks</h2>
                                    <p class="mt-1 text-sm text-zinc-400">Search and filter active task templates across sessions and rotations.</p>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.3-4.3" stroke-linecap="round"></path>
                                            </svg>
                                        </div>
                                        <input
                                            v-model="taskListFilters.search"
                                            type="text"
                                            class="field !pl-9 !pr-9"
                                            placeholder="Search tasks by name..."
                                            aria-label="Search tasks by name"
                                        >
                                        <button
                                            v-if="taskListFilters.search"
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-200"
                                            aria-label="Clear search"
                                            @click="taskListFilters.search = ''"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                        <label class="space-y-1 text-xs font-bold text-zinc-400">
                                            <span>Rotation</span>
                                            <select v-model="taskListFilters.collection_id" class="field">
                                                <option value="all">All rotations</option>
                                                <option v-for="collection in taskCollections" :key="collection.id" :value="collection.id">{{ collectionDisplayName(collection) }}</option>
                                            </select>
                                        </label>
                                        <label class="space-y-1 text-xs font-bold text-zinc-400">
                                            <span>Task type</span>
                                            <select v-model="taskListFilters.task_type" class="field">
                                                <option value="all">All types</option>
                                                <option value="daily">Daily only</option>
                                                <option value="weekly">Weekly only</option>
                                                <option value="monthly">Monthly only</option>
                                            </select>
                                        </label>
                                        <button type="button" class="small-button h-11 px-4 text-xs font-bold" @click="taskListFilters = { collection_id: 'all', task_type: 'all', search: '' }">Reset filters</button>
                                    </div>
                                </div>
                            </div>
                            <section v-for="(session, index) in activeSessions" :key="session.id">
                                <header class="mb-2 flex justify-between"><h3 class="font-black uppercase" :class="sessionTone(index)">{{ session.name }}</h3><span class="text-xs text-zinc-500">{{ filteredTaskEditorItemsFor(session.id).length }} task{{ filteredTaskEditorItemsFor(session.id).length === 1 ? '' : 's' }}</span></header>
                                <div class="space-y-2">
                                    <article v-for="item in filteredTaskEditorItemsFor(session.id)" :key="`${item.type}:${item.id}`" class="flex items-center justify-between gap-3 rounded-xl border border-zinc-700 bg-zinc-900 p-3.5">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-zinc-100">{{ item.taskName }}</p>
                                                <span class="rounded-full border px-2 py-0.5 text-xs font-black uppercase" :class="item.type === 'monthly' ? 'border-purple-400/40 bg-purple-400/10 text-purple-300' : (item.type === 'weekly' ? 'border-sky-400/40 bg-sky-400/10 text-sky-300' : 'border-zinc-700 bg-zinc-800 text-zinc-300')">{{ taskTypeLabel(item.type) }}</span>
                                                <span v-for="collection in taskCollectionPills(item)" :key="`${item.type}:${item.id}:${collection.key}`" class="max-w-40 truncate rounded-full border px-2 py-0.5 text-xs font-black" :class="collection.collectionId ? collectionTone(collection.collectionId) : 'border-zinc-700 bg-zinc-800 text-zinc-300'">{{ collection.name }}</span>
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                                                <span v-if="item.finishTime" class="font-semibold text-zinc-300">Finish: {{ formatTime12(item.finishTime) }}</span>
                                                <span v-if="item.type === 'weekly'">• Due {{ weekdayName(item.dueWeekday) }}</span>
                                                <span v-else-if="item.type === 'monthly'">• Last Friday of month</span>
                                                <span v-else-if="item.type === 'daily'">• {{ formatDaysOfWeek(item.daysOfWeek) }}</span>
                                                <span v-if="item.description" class="text-zinc-500">• {{ item.description.slice(0, 40) }}{{ item.description.length > 40 ? '...' : '' }}</span>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button class="small-button inline-flex h-9 w-9 items-center justify-center p-0" aria-label="Edit task" title="Edit task" @click="openEdit(item.type, item)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m4 16.5-.7 4.2 4.2-.7L18.8 8.7l-3.5-3.5L4 16.5Z" stroke-linejoin="round"></path><path d="m13.8 6.7 3.5 3.5" stroke-linecap="round"></path></svg></button>
                                            <button class="small-button text-rose-300" @click="deleteTemplate(item.type, item)">Archive</button>
                                        </div>
                                    </article>
                                </div>
                            </section>
                        </div>
                    </div>

                </section>
            </div>
        </main>

        <!-- Task Details Modal -->
        <div v-if="viewingTaskDetails" class="modal-backdrop">
            <div class="modal-card max-w-lg">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="rounded-md border px-2 py-0.5 text-xs font-black uppercase" :class="viewingTaskDetails.isMonthly ? 'border-purple-400/40 bg-purple-400/10 text-purple-300' : (viewingTaskDetails.isWeekly ? 'border-sky-400/40 bg-sky-400/10 text-sky-300' : 'border-zinc-700 bg-zinc-800 text-zinc-300')">{{ taskTypeLabel(viewingTaskDetails.type) }}</span>
                        <h2 class="mt-2 text-xl font-black text-zinc-100">{{ viewingTaskDetails.text }}</h2>
                    </div>
                    <button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Tutup" title="Tutup" @click="closeTaskDetails">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl border border-zinc-700 bg-zinc-900/60 p-3.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Masa & Jadual Sesi</span>
                        <p class="mt-1 text-sm font-semibold text-zinc-200">{{ viewingTaskDetails.timeSpanFormatted }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400">Sesi: {{ viewingTaskDetails.sessionName }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-700 bg-zinc-900/60 p-3.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Penerangan / Arahan Tugasan</span>
                        <p v-if="viewingTaskDetails.description" class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-zinc-200">{{ viewingTaskDetails.description }}</p>
                        <p v-else class="mt-1 text-xs italic text-zinc-500">Tiada penerangan tambahan untuk tugasan ini.</p>
                    </div>

                    <div v-if="viewingTaskDetails.isWeekly" class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-3.5 text-xs text-sky-200">
                        <strong>Tugasan Mingguan:</strong> Perlu diselesaikan sebelum atau pada {{ displayDate(viewingTaskDetails.originalDueDate) }}.
                    </div>

                    <div v-if="viewingTaskDetails.isMonthly" class="rounded-xl border border-purple-500/30 bg-purple-500/5 p-3.5 text-xs text-purple-200">
                        <strong>Tugasan Bulanan:</strong> Dijadualkan pada hari Jumaat terakhir setiap bulan.
                    </div>
                </div>

                <button type="button" class="primary-button mt-5" @click="closeTaskDetails">Tutup</button>
            </div>
        </div>

        <div v-if="evidenceTask" class="modal-backdrop">
            <form class="modal-card" @submit.prevent="completeTask">
                <div class="flex justify-between gap-3"><div><h2 class="font-black">Foto Bukti</h2><p class="mt-1 text-sm text-zinc-400">{{ evidenceTask.text }}</p></div><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Tutup" title="Tutup" @click="closeEvidence"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <div class="mt-5 rounded-2xl border border-dashed border-zinc-600 p-5 text-center">
                    <strong class="block text-sm">Tambah foto bukti</strong>
                    <span class="mt-2 block text-xs text-zinc-500">JPEG, PNG atau WebP. Maksimum {{ uploadLimits.maxFiles }} foto, {{ uploadLimits.maxFileMb }} MB setiap satu, jumlah sehingga {{ uploadLimits.maxRequestMb }} MB.</span>
                    <ul class="mt-3 space-y-1 text-left text-xs leading-relaxed text-zinc-300">
                        <li>Ambil gambar yang jelas dan cukup luas untuk mengenal pasti kawasan yang telah dibersihkan.</li>
                        <li>Pastikan pencahayaan mencukupi dan elakkan wajah atau maklumat peribadi.</li>
                    </ul>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <label class="small-button cursor-pointer">Ambil foto<input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only" @change="selectEvidence"></label>
                        <label class="small-button cursor-pointer">Pilih galeri<input type="file" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectEvidence"></label>
                    </div>
                </div>
                <p v-if="errorFor('evidence', 'photos')" :id="errorId('evidence', 'photos')" class="field-error mt-3">{{ errorFor('evidence', 'photos') }}</p>
                <div v-if="evidencePreviews.length" class="mt-4 grid grid-cols-3 gap-2"><div v-for="(preview, index) in evidencePreviews" :key="preview" class="relative aspect-square overflow-hidden rounded-xl bg-zinc-800"><img :src="preview" alt="Pratonton bukti" class="h-full w-full object-cover"><button type="button" class="absolute right-1 top-1 rounded-full bg-black/75 px-2 py-1 text-xs font-bold" :aria-label="`Buang foto ${index + 1}`" @click="removeEvidence(index)">Buang</button></div></div>
                <label class="mt-4 block text-sm font-bold">Nota tugasan <span class="font-normal text-zinc-500">(pilihan)</span><textarea v-model.trim="completionNote" maxlength="500" rows="3" class="field mt-2 h-auto py-2" placeholder="Contoh: kawasan ditutup atau bekalan tidak mencukupi" v-bind="validationAttrs('evidence', 'note')"></textarea></label>
                <p v-if="errorFor('evidence', 'note')" :id="errorId('evidence', 'note')" class="field-error">{{ errorFor('evidence', 'note') }}</p>
                <button :disabled="busy || !evidenceFiles.length" class="primary-button mt-5">Hantar bukti & tandakan selesai</button>
                <p class="mt-3 text-center text-xs text-amber-300">Tugasan yang selesai tidak boleh dibuka semula oleh cleaner.</p>
            </form>
        </div>

        <div v-if="viewingEvidence" class="modal-backdrop">
            <div class="modal-card max-w-3xl">
                <div class="flex justify-between gap-3"><div><h2 class="font-black">{{ viewingEvidence.text }}</h2><p class="mt-1 text-xs text-zinc-500">{{ formatTimestamp(viewingEvidence.completedAt) }} - {{ viewingEvidence.timeSpanFormatted }}</p></div><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Close" title="Close" @click="viewingEvidence = null"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <p v-if="viewingEvidence.type === 'weekly'" class="mt-3 text-xs text-sky-300">Weekly - due {{ displayAdminDate(viewingEvidence.originalDueDate) }} - final scheduled date {{ displayAdminDate(viewingEvidence.scheduledDate) }}</p>
                <p v-else-if="viewingEvidence.type === 'monthly'" class="mt-3 text-xs text-purple-300">Monthly - scheduled date {{ displayAdminDate(viewingEvidence.scheduledDate) }}</p>
                <p v-if="viewingEvidence.completionNote" class="mt-3 rounded-xl border border-zinc-700 bg-zinc-900 p-3 text-sm text-zinc-300">Note: {{ viewingEvidence.completionNote }}</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2"><a v-for="photo in viewingEvidence.evidence" :key="photo.id" :href="photo.url" target="_blank" rel="noopener" class="overflow-hidden rounded-xl border border-zinc-700 bg-zinc-900"><img :src="photo.url" loading="lazy" alt="Task evidence photo" class="max-h-96 w-full object-contain"></a></div>
                <button v-if="viewingEvidence.canReopen" class="small-button mt-5 border-amber-400/50 text-amber-300 transition hover:border-amber-300 hover:bg-amber-400/10 hover:text-amber-200" @click="openReopen(viewingEvidence); viewingEvidence = null">Reopen completed task</button>
            </div>
        </div>

        <div v-if="reopeningTask" class="modal-backdrop">
            <form class="modal-card" @submit.prevent="reopenTask">
                <div class="flex justify-between gap-3"><div><h2 class="font-black">Reopen completed task</h2><p class="mt-1 text-sm text-zinc-400">{{ reopeningTask.text }}</p></div><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Close" title="Close" @click="closeReopen"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <p class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3 text-sm text-amber-100">The previous proof will no longer appear in task history. It is retained securely in the audit log.</p>
                <label class="mt-5 block text-sm font-bold">Reason for reopening<textarea v-model.trim="reopenReason" required maxlength="1000" rows="4" class="field mt-2 h-auto py-3" placeholder="Explain why the proof was uploaded by mistake" v-bind="validationAttrs('reopen', 'reason')"></textarea></label>
                <p v-if="errorFor('reopen', 'reason')" :id="errorId('reopen', 'reason')" class="field-error">{{ errorFor('reopen', 'reason') }}</p>
                <button :disabled="busy || !reopenReason.trim()" class="primary-button mt-5">Reopen task</button>
            </form>
        </div>

        <div v-if="publicHolidayEditing" class="modal-backdrop">
            <form class="modal-card" @submit.prevent="savePublicHoliday">
                <div class="flex justify-between gap-3"><div><h2 class="font-black">Edit Public Holiday</h2><p class="mt-1 text-sm text-zinc-400">Update this future office-closure date.</p></div><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" :disabled="busy" aria-label="Close" title="Close" @click="closePublicHolidayEdit"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <div class="mt-5 space-y-3">
                    <label class="form-label" for="edit-public-holiday-name">Holiday name<input id="edit-public-holiday-name" v-model.trim="publicHolidayEditForm.name" required maxlength="100" class="field" v-bind="validationAttrs('publicHolidayEdit', 'name')"></label>
                    <p v-if="errorFor('publicHolidayEdit', 'name')" :id="errorId('publicHolidayEdit', 'name')" class="field-error">{{ errorFor('publicHolidayEdit', 'name') }}</p>
                    <label class="form-label" for="edit-public-holiday-date">Closure date<input id="edit-public-holiday-date" v-model="publicHolidayEditForm.date" required type="date" :min="tomorrow" class="field" v-bind="validationAttrs('publicHolidayEdit', 'date')"></label>
                    <p v-if="errorFor('publicHolidayEdit', 'date')" :id="errorId('publicHolidayEdit', 'date')" class="field-error">{{ errorFor('publicHolidayEdit', 'date') }}</p>
                </div>
                <button :disabled="busy" class="primary-button mt-5">Save changes</button>
            </form>
        </div>

        <!-- Edit Session Modal -->
        <div v-if="sessionEditing" class="modal-backdrop">
            <form class="modal-card" @submit.prevent="saveSession">
                <div class="flex justify-between"><h2 class="font-black">Edit Work Session</h2><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Close" title="Close" @click="sessionEditing = null"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <div class="mt-5 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="form-label">
                            Start time
                            <input v-model="sessionEditForm.start_time" required type="time" class="field" v-bind="validationAttrs('sessionEdit', 'start_time')">
                        </label>
                        <label class="form-label">
                            End time
                            <input v-model="sessionEditForm.end_time" required type="time" class="field" v-bind="validationAttrs('sessionEdit', 'end_time')">
                        </label>
                    </div>
                    <p v-if="errorFor('sessionEdit', 'start_time')" :id="errorId('sessionEdit', 'start_time')" class="field-error">{{ errorFor('sessionEdit', 'start_time') }}</p>
                    <p v-if="errorFor('sessionEdit', 'end_time')" :id="errorId('sessionEdit', 'end_time')" class="field-error">{{ errorFor('sessionEdit', 'end_time') }}</p>

                    <div class="rounded-xl border border-zinc-700 bg-zinc-950 p-3 text-xs">
                        <span class="text-zinc-500 font-bold uppercase tracking-wider block">Formatted name</span>
                        <strong class="text-sm text-zinc-200 mt-1 block">{{ formatSessionPreview(sessionEditForm.start_time, sessionEditForm.end_time) }}</strong>
                    </div>
                </div>
                <button class="primary-button mt-5">Save changes</button>
            </form>
        </div>

        <!-- Edit Task Modal -->
        <div v-if="editing" class="modal-backdrop">
            <form class="modal-card" @submit.prevent="saveEdit">
                <div class="flex justify-between"><h2 class="font-black">Edit Template</h2><button type="button" class="small-button inline-flex h-9 w-9 shrink-0 items-center justify-center p-0" aria-label="Close" title="Close" @click="editing = null"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>
                <div class="mt-5 space-y-3">
                    <div class="form-label"><span>Task type</span><div class="readonly-field" role="note" :aria-label="`Task type: ${taskTypeLabel(editForm.task_type)}. This cannot be changed after creation.`"><span class="readonly-field__value"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"></path></svg>{{ taskTypeLabel(editForm.task_type) }}</span><span class="readonly-field__badge">Locked</span></div></div>
                    <label class="form-label">Task name<input v-model.trim="editForm.task_name" required maxlength="255" class="field" placeholder="For example, Mop the lobby" v-bind="validationAttrs('taskEdit', 'task_name')"></label>
                    <p v-if="errorFor('taskEdit', 'task_name')" :id="errorId('taskEdit', 'task_name')" class="field-error">{{ errorFor('taskEdit', 'task_name') }}</p>

                    <label class="form-label">Description / details <span class="font-normal text-zinc-500">(optional)</span>
                        <textarea v-model.trim="editForm.description" rows="3" class="field h-auto py-2" placeholder="Specific guidelines..."></textarea>
                    </label>

                    <label class="form-label">Work session<select v-model="editForm.task_session_id" required class="field" v-bind="validationAttrs('taskEdit', 'task_session_id')"><option v-for="session in activeSessions" :key="session.id" :value="session.id">{{ session.name }}</option></select></label>
                    <p v-if="errorFor('taskEdit', 'task_session_id')" :id="errorId('taskEdit', 'task_session_id')" class="field-error">{{ errorFor('taskEdit', 'task_session_id') }}</p>

                    <label class="form-label">Finish / due time
                        <input v-model="editForm.finish_time" required type="time" class="field" v-bind="validationAttrs('taskEdit', 'finish_time')">
                    </label>
                    <p v-if="errorFor('taskEdit', 'finish_time')" :id="errorId('taskEdit', 'finish_time')" class="field-error">{{ errorFor('taskEdit', 'finish_time') }}</p>

                    <label v-if="editForm.task_type === 'weekly'" class="form-label">Weekly due day<select v-model.number="editForm.due_weekday" class="field" v-bind="validationAttrs('taskEdit', 'due_weekday')"><option v-for="day in 5" :key="day" :value="day">{{ weekdayName(day) }}</option></select></label>

                    <div v-if="editForm.task_type === 'daily'" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500">Days of week</p>
                            <button type="button" class="text-xs font-semibold text-rose-400 hover:underline" @click="toggleAllDays(editForm)">
                                {{ editForm.days_of_week?.length === 5 ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-2">
                            <label
                                v-for="day in WEEKDAY_OPTIONS"
                                :key="day.value"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-colors"
                                :class="editForm.days_of_week?.includes(day.value) ? 'border-rose-500/60 bg-rose-500/10 text-rose-200' : 'border-zinc-700 bg-zinc-800/60 text-zinc-400 hover:border-zinc-600'"
                            >
                                <input
                                    v-model="editForm.days_of_week"
                                    type="checkbox"
                                    :value="day.value"
                                    class="accent-rose-500"
                                >
                                <span>{{ day.label }}</span>
                            </label>
                        </div>
                        <p v-if="errorFor('taskEdit', 'days_of_week')" :id="errorId('taskEdit', 'days_of_week')" class="field-error mt-2">{{ errorFor('taskEdit', 'days_of_week') }}</p>
                    </div>
                    <label class="form-label">Where this task appears<select v-model="editForm.collection_mode" class="field"><option value="single">One rotation</option><option value="multiple">Selected rotations</option><option value="all">All rotations</option></select></label>
                    <label v-if="editForm.collection_mode === 'single'" class="form-label">Rotation<select v-model="editForm.single_collection_id" required class="field" v-bind="validationAttrs('taskEdit', 'task_collection_ids')"><option v-for="collection in taskCollections" :key="collection.id" :value="collection.id">{{ collectionDisplayName(collection) }}</option></select></label>
                    <div v-else-if="editForm.collection_mode === 'multiple'" class="rounded-2xl border border-zinc-700 bg-zinc-900 p-3">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500">Choose rotations</p>
                        <label v-for="collection in taskCollections" :key="collection.id" class="mt-3 flex items-center gap-3 text-sm text-zinc-300"><input v-model="editForm.task_collection_ids" type="checkbox" :value="collection.id"><span>{{ collectionDisplayName(collection) }}</span></label>
                    </div>
                    <p v-if="errorFor('taskEdit', 'task_collection_ids')" :id="errorId('taskEdit', 'task_collection_ids')" class="field-error">{{ errorFor('taskEdit', 'task_collection_ids') }}</p>
                </div>
                <button class="primary-button mt-5">Save changes</button>
            </form>
        </div>

        <div v-if="confirmation" class="modal-backdrop">
            <section class="modal-card" role="alertdialog" aria-modal="true" aria-labelledby="confirmation-title" aria-describedby="confirmation-description">
                <h2 id="confirmation-title" class="font-black">{{ confirmation.title }}</h2>
                <p id="confirmation-description" class="mt-3 text-sm leading-relaxed text-zinc-300">{{ confirmation.description }}</p>
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button type="button" class="small-button" :disabled="busy" @click="closeConfirmation">Cancel</button>
                    <button type="button" class="small-button border-rose-400/50 bg-rose-400/10 text-rose-200" :disabled="busy" @click="confirmAction">{{ confirmation.confirmLabel }}</button>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.field { width: 100%; height: 2.75rem; border-radius: .75rem; border: 1px solid rgb(63 63 70); background: #121212; padding: 0 .75rem; font-size: .875rem; outline: none; }
select.field {
    border-radius: .75rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23a1a1aa' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem 1.25rem;
    padding-right: 2.25rem;
}
.field:focus { border-color: #ED4264; }
.field[aria-invalid='true'] { border-color: #fb7185; }
.form-label { display: grid; gap: .35rem; color: rgb(212 212 216); font-size: .8125rem; font-weight: 650; }
.readonly-field { display: flex; min-height: 2.75rem; align-items: center; justify-content: space-between; gap: .75rem; border: 1px solid rgb(82 82 91); border-radius: .75rem; background: rgb(39 39 42 / .65); padding: 0 .75rem; color: rgb(212 212 216); }
.readonly-field__value { display: inline-flex; min-width: 0; align-items: center; gap: .5rem; font-size: .875rem; font-weight: 650; }
.readonly-field__value svg { width: 1rem; height: 1rem; color: rgb(161 161 170); }
.readonly-field__badge { border: 1px solid rgb(82 82 91); border-radius: 9999px; padding: .125rem .45rem; color: rgb(161 161 170); font-size: .625rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
.field-error { color: #fda4af; font-size: .8125rem; font-weight: 600; line-height: 1.35; }
.primary-button { width: 100%; height: 2.75rem; border-radius: .75rem; background: linear-gradient(to right, #ED4264, #FFEDBC); color: #18181b; font-size: .875rem; font-weight: 560; }
.primary-button:disabled { opacity: .5; }
.small-button { border: 1px solid rgb(82 82 91); border-radius: .6rem; padding: .45rem .65rem; font-size: .8125rem; font-weight: 560; }
.small-button:disabled { opacity: .25; }
.checklist-date-weekday { font-weight: 650; line-height: 1.2; }
.checklist-date-value { font-weight: 450; line-height: 1.35; }
.theme-toggle { display: inline-flex; min-width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; gap: .4rem; padding: 0 .65rem; font-size: .75rem; font-weight: 650; }
.theme-toggle svg { width: 1.125rem; height: 1.125rem; }
.history-nav-button,
.calendar-nav-button { display: inline-flex; width: 2.75rem; height: 2.75rem; flex: 0 0 auto; align-items: center; justify-content: center; border: 1px solid rgb(82 82 91); border-radius: .75rem; background: #121212; color: rgb(228 228 231); }
.history-nav-button svg,
.calendar-nav-button svg { width: 1rem; height: 1rem; }
.history-nav-button:hover,
.calendar-nav-button:hover { border-color: rgb(161 161 170); background: #27272a; }
.rotation-calendar-scroll { overflow-x: auto; padding-bottom: .25rem; }
.rotation-calendar { min-width: 42rem; }
.rotation-calendar-header,
.rotation-calendar-week { display: grid; grid-template-columns: 1.25rem repeat(7, minmax(0, 1fr)); gap: .25rem; }
.rotation-calendar-header { margin-bottom: .35rem; }
.rotation-calendar-week { position: relative; margin-top: .25rem; }
.rotation-calendar-week-label { grid-column: 1; grid-row: 1; display: flex; min-height: 4.25rem; align-items: center; justify-content: center; color: rgb(161 161 170); font-size: .5625rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; writing-mode: vertical-rl; transform: rotate(180deg); }
.rotation-calendar-cell { grid-row: 1; min-height: 4.25rem; }
.rotation-calendar-band { position: relative; z-index: 1; grid-column: 3 / span 5; grid-row: 1; align-self: end; overflow: hidden; margin: 0 .35rem .35rem; border-width: 1px; border-style: solid; border-radius: 9999px; font-size: .625rem; font-weight: 700; line-height: 1.15; pointer-events: none; }
.rotation-calendar-band__segments { position: absolute; inset: 0; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); }
.rotation-calendar-band__segment { min-width: 0; }
.rotation-calendar-band__label { position: relative; z-index: 1; display: block; overflow: hidden; padding: .125rem .45rem; text-align: center; text-overflow: ellipsis; white-space: nowrap; }
.history-selected-date { display: grid; gap: .15rem; min-width: min(100%, 16rem); }
.history-selected-date span { color: rgb(161 161 170); font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.history-selected-date strong { color: rgb(244 244 245); font-size: .9375rem; }
.cleaner-logout { color: #fb7185; }
.cleaner-logout:hover { color: #fda4af; }
.theme-toggle:focus-visible,
.cleaner-logout:focus-visible { outline: 2px solid #fb7185; outline-offset: 3px; }
.modal-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: flex-end; justify-content: center; overflow-y: auto; background: rgb(0 0 0 / .78); padding: 1rem; }
.modal-card { width: 100%; max-width: 32rem; max-height: 92vh; overflow-y: auto; border: 1px solid rgb(82 82 91); border-radius: 1rem; background: #171717; padding: 1.25rem; box-shadow: 0 25px 50px -12px rgb(0 0 0 / .7); }
.theme-light { background: #f8fafc; color: #18181b; }
.theme-light :is(header, .modal-card, [class*="bg-[#121212]"]) { background-color: #f8fafc; }
.theme-light [class*="bg-zinc-900"], .theme-light [class*="bg-zinc-950"] { background-color: #fff; }
.theme-light [class*="bg-zinc-800"] { background-color: #e4e4e7; }
.theme-light [class*="bg-rose-950"] { background-color: #fff1f2; }
.theme-light [class*="bg-emerald-950"] { background-color: #ecfdf5; }
.theme-light [class*="text-zinc-100"], .theme-light [class*="text-zinc-200"] { color: #18181b; }
.theme-light [class*="text-zinc-300"] { color: #3f3f46; }
.theme-light [class*="text-zinc-400"], .theme-light [class*="text-zinc-500"] { color: #52525b; }
.theme-light [class*="text-zinc-600"] { color: #71717a; }
.theme-light [class*="text-zinc-700"], .theme-light [class*="text-zinc-800"] { color: #94a3b8; }
.theme-light [class*="text-rose-100"],
.theme-light [class*="text-rose-200"],
.theme-light [class*="text-rose-300"],
.theme-light [class*="text-rose-400"],
.theme-light [class*="text-rose-500"],
.theme-light [class*="text-red-"] { color: #e11d48; }
.theme-light [class*="text-emerald-100"], .theme-light [class*="text-emerald-300"] { color: #047857; }
.theme-light [class*="text-amber-300"] { color: #a16207; }
.theme-light [class*="text-sky-300"] { color: #0369a1; }
.theme-light [class*="text-violet-300"],
.theme-light [class*="text-purple-300"] { color: #6d28d9; }
.theme-light [class*="border-zinc-600"] { border-color: #a1a1aa; }
.theme-light [class*="border-zinc-700"], .theme-light [class*="border-zinc-800"] { border-color: #d4d4d8; }
.theme-light .field,
.theme-light .small-button,
.theme-light .theme-toggle { background-color: #fff; border-color: #cbd5e1; color: #18181b; color-scheme: light; }
.theme-light .small-button[class*="text-rose-"],
.theme-light .small-button[class*="text-red-"] {
    color: #e11d48;
    border-color: #fecdd3;
}
.theme-light .small-button[class*="text-rose-"]:hover,
.theme-light .small-button[class*="text-red-"]:hover {
    color: #be123c;
    background-color: #fff1f2;
    border-color: #fda4af;
}
.theme-light .readonly-field { background-color: #f8fafc; border-color: #cbd5e1; color: #334155; }
.theme-light .readonly-field__value svg,
.theme-light .readonly-field__badge { color: #64748b; }
.theme-light .readonly-field__badge { border-color: #cbd5e1; background-color: #fff; }
.theme-light .form-label,
.theme-light .sunday-date-picker__label { color: #334155; }
.theme-light .sunday-date-picker__trigger,
.theme-light .sunday-date-picker__popover,
.theme-light .history-nav-button,
.theme-light .calendar-nav-button { background-color: #fff; border-color: #cbd5e1; color: #18181b; }
.theme-light .sunday-date-picker__days button.is-outside { color: #94a3b8; }
.theme-light .history-selected-date span { color: #64748b; }
.theme-light .history-selected-date strong { color: #0f172a; }
.theme-light .rotation-calendar-week-label { color: #64748b; }
.theme-light .field { caret-color: #be123c; }
.theme-light .field::placeholder { color: #71717a; opacity: 1; }
.theme-light .field:focus { border-color: #e11d48; box-shadow: 0 0 0 3px rgb(225 29 72 / .12); }
.theme-light .field option { background-color: #fff; color: #18181b; }
.theme-light select.field {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
}
.theme-light .small-button:hover,
.theme-light .theme-toggle:hover { background-color: #f1f5f9; border-color: #94a3b8; }
.theme-light .theme-toggle:focus-visible,
.theme-light .cleaner-logout:focus-visible { outline-color: #be123c; }
.theme-light .cleaner-logout { color: #be123c; }
.theme-light .cleaner-logout:hover { color: #9f1239; }
.theme-light .checklist-date-weekday { color: #18181b; }
.theme-light .admin-tab-active { background-color: #fff1f2; border-color: #fda4af; color: #be123c; }
.theme-light .admin-tab:not(.admin-tab-active) { border-color: transparent; }
.theme-light .admin-tab:not(.admin-tab-active):hover { background-color: #fff; border-color: transparent; color: #18181b; }

.drawer-backdrop-enter-active,
.drawer-backdrop-leave-active {
    transition: opacity 0.25s ease;
}
.drawer-backdrop-enter-from,
.drawer-backdrop-leave-to {
    opacity: 0;
}

.drawer-panel-enter-active,
.drawer-panel-leave-active {
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-panel-enter-from,
.drawer-panel-leave-to {
    transform: translateX(-100%);
}

@media (min-width: 640px) { .modal-backdrop { align-items: center; } }
@media (min-width: 64rem) {
    .field,
    .primary-button { font-size: .9375rem; }
    .primary-button,
    .small-button { font-weight: 420; }
    .small-button { font-size: .8125rem; }
}
</style>
