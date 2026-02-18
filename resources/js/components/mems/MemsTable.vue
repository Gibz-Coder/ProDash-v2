<script setup lang="ts" generic="T extends Record<string, any>">
interface Props {
    headers: string[];
    data: T[];
    totalRow?: Record<string, any>;
    className?: string;
}

defineProps<Props>();
</script>

<template>
    <table :class="['mems-table', className]">
        <thead>
            <tr>
                <th v-for="header in headers" :key="header">{{ header }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, index) in data" :key="index">
                <td v-for="(value, key) in row" :key="key">
                    {{ value }}
                </td>
            </tr>
            <tr v-if="totalRow" style="font-weight: bold; background-color: #a4c2d6">
                <td v-for="(value, key) in totalRow" :key="key">
                    {{ value }}
                </td>
            </tr>
        </tbody>
    </table>
</template>
