<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    theme: { type: String, default: 'dark' },
});

const emit = defineEmits(['update:modelValue']);
const open = ref(false);
const month = ref(monthFor(props.modelValue));
const weekdays = Object.freeze(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']);

const monthLabel = computed(() => {
    const [year, value] = month.value.split('-').map(Number);

    return new Intl.DateTimeFormat('en-MY', { month: 'long', year: 'numeric', timeZone: 'Asia/Kuala_Lumpur' })
        .format(new Date(Date.UTC(year, value - 1, 1, 12)));
});

const days = computed(() => {
    const [year, value] = month.value.split('-').map(Number);
    const first = new Date(year, value - 1, 1, 12);
    const last = new Date(year, value, 0, 12);
    const gridStart = shiftDate(toIso(first), -first.getDay());
    const gridEnd = shiftDate(toIso(last), 6 - last.getDay());
    const result = [];

    for (let cursor = gridStart; cursor <= gridEnd; cursor = shiftDate(cursor, 1)) {
        result.push({
            value: cursor,
            day: Number(cursor.slice(8, 10)),
            inMonth: cursor.startsWith(`${month.value}-`),
        });
    }

    return result;
});

watch(() => props.modelValue, (value) => {
    if (value && /^\d{4}-\d{2}-\d{2}$/.test(value)) month.value = value.slice(0, 7);
});

function monthFor(value) {
    if (/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return value.slice(0, 7);

    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Kuala_Lumpur', year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(new Date()).slice(0, 7);
}

function toIso(date) {
    return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
}

function shiftDate(value, offset) {
    const [year, monthValue, day] = value.split('-').map(Number);
    const date = new Date(year, monthValue - 1, day, 12);
    date.setDate(date.getDate() + offset);

    return toIso(date);
}

function shiftMonth(offset) {
    const [year, value] = month.value.split('-').map(Number);
    const date = new Date(year, value - 1, 1, 12);
    date.setMonth(date.getMonth() + offset);
    month.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function choose(value) {
    if (isDisabled(value)) return;
    emit('update:modelValue', value);
    open.value = false;
}

function isDisabled(value) {
    return !!((props.min && value < props.min) || (props.max && value > props.max));
}

function display(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return 'Select date';
    const [year, monthValue, day] = value.split('-');

    return `${day}/${monthValue}/${year}`;
}
</script>

<template>
    <div class="sunday-date-picker" :class="`is-${theme}`" @keydown.esc="open = false">
        <span v-if="label" class="sunday-date-picker__label">{{ label }}</span>
        <button type="button" class="sunday-date-picker__trigger" :disabled="disabled" :aria-expanded="open" @click="open = !open">
            <span>{{ display(modelValue) }}</span>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4m8-4v4M4 10h16"></path></svg>
        </button>
        <div v-if="open" class="sunday-date-picker__popover" role="dialog" :aria-label="label || 'Select date'">
            <div class="sunday-date-picker__header">
                <button type="button" aria-label="Previous month" @click="shiftMonth(-1)"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m12.5 4.5-5.5 5.5 5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
                <strong>{{ monthLabel }}</strong>
                <button type="button" aria-label="Next month" @click="shiftMonth(1)"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.5 5.5 5.5-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
            </div>
            <div class="sunday-date-picker__weekdays"><span v-for="day in weekdays" :key="day">{{ day }}</span></div>
            <div class="sunday-date-picker__days">
                <button v-for="day in days" :key="day.value" type="button" :disabled="isDisabled(day.value)" :class="[!day.inMonth && 'is-outside', day.value === modelValue && 'is-selected']" :aria-pressed="day.value === modelValue" @click="choose(day.value)">{{ day.day }}</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sunday-date-picker { position: relative; display: grid; gap: .35rem; min-width: 10rem; }
.sunday-date-picker__label { color: rgb(212 212 216); font-size: .8125rem; font-weight: 650; }
.sunday-date-picker__trigger { display: flex; height: 2.75rem; width: 100%; align-items: center; justify-content: space-between; gap: .75rem; border: 1px solid rgb(63 63 70); border-radius: .75rem; background: #121212; padding: 0 .75rem; text-align: left; font-size: .875rem; outline: none; }
.sunday-date-picker__trigger:focus-visible { border-color: #ED4264; box-shadow: 0 0 0 3px rgb(237 66 100 / .16); }
.sunday-date-picker__trigger:disabled { opacity: .5; }
.sunday-date-picker__trigger svg { width: 1rem; height: 1rem; flex: 0 0 auto; }
.sunday-date-picker__popover { position: absolute; z-index: 50; top: calc(100% + .4rem); left: 0; width: min(19rem, calc(100vw - 2.5rem)); border: 1px solid rgb(82 82 91); border-radius: .9rem; background: #171717; padding: .75rem; box-shadow: 0 20px 40px rgb(0 0 0 / .28); }
.sunday-date-picker__header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .65rem; font-size: .8125rem; }
.sunday-date-picker__header button { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 1px solid rgb(82 82 91); border-radius: .55rem; }
.sunday-date-picker__header svg { width: 1rem; height: 1rem; }
.sunday-date-picker__weekdays, .sunday-date-picker__days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .2rem; text-align: center; }
.sunday-date-picker__weekdays { margin-bottom: .25rem; color: rgb(161 161 170); font-size: .625rem; font-weight: 700; text-transform: uppercase; }
.sunday-date-picker__days button { aspect-ratio: 1; border-radius: .45rem; font-size: .75rem; }
.sunday-date-picker__days button:hover:not(:disabled), .sunday-date-picker__days button:focus-visible { background: rgb(237 66 100 / .18); outline: none; }
.sunday-date-picker__days button.is-selected { background: #ED4264; color: white; font-weight: 700; }
.sunday-date-picker__days button.is-outside { color: rgb(113 113 122); }
.sunday-date-picker__days button:disabled { opacity: .3; }
.sunday-date-picker.is-light .sunday-date-picker__label { color: #334155; }
.sunday-date-picker.is-light .sunday-date-picker__trigger { border-color: #cbd5e1; background: #fff; color: #0f172a; }
.sunday-date-picker.is-light .sunday-date-picker__trigger:focus-visible { border-color: #e11d48; box-shadow: 0 0 0 3px rgb(225 29 72 / .12); }
.sunday-date-picker.is-light .sunday-date-picker__popover { border-color: #cbd5e1; background: #fff; color: #0f172a; box-shadow: 0 20px 40px rgb(15 23 42 / .12); }
.sunday-date-picker.is-light .sunday-date-picker__header button { border-color: #cbd5e1; background: #fff; color: #334155; }
.sunday-date-picker.is-light .sunday-date-picker__header button:hover { background: #f1f5f9; }
.sunday-date-picker.is-light .sunday-date-picker__weekdays { color: #64748b; }
.sunday-date-picker.is-light .sunday-date-picker__days button { color: #0f172a; }
.sunday-date-picker.is-light .sunday-date-picker__days button.is-outside { color: #94a3b8; }
.sunday-date-picker.is-light .sunday-date-picker__days button:hover:not(:disabled), .sunday-date-picker.is-light .sunday-date-picker__days button:focus-visible { background: #ffe4e6; }
</style>
