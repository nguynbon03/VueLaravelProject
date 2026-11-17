<script setup lang="ts">
export interface TableColumn {
  key: string
  label: string
  align?: 'left' | 'right'
}

withDefaults(
  defineProps<{
    columns: readonly TableColumn[]
    hasRows: boolean
    emptyMessage?: string
  }>(),
  {
    emptyMessage: 'Không có bản ghi nào phù hợp.',
  },
)
</script>

<template>
  <div class="data-table-box">
    <div v-if="!hasRows" class="muted-block">
      <p>{{ emptyMessage }}</p>
    </div>

    <div v-else class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="{ 'align-right': col.align === 'right' }"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <slot />
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.align-right {
  text-align: right;
}
</style>
