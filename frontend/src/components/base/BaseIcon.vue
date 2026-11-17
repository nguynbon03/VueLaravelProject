<script setup lang="ts">
export type IconName =
  | 'bag'
  | 'search'
  | 'user'
  | 'users'
  | 'shield'
  | 'check'
  | 'check-circle'
  | 'alert'
  | 'x'
  | 'plus'
  | 'minus'
  | 'trash'
  | 'arrow-right'
  | 'arrow-left'
  | 'chevron-right'
  | 'chevron-left'
  | 'star'
  | 'package'
  | 'truck'
  | 'credit-card'
  | 'qr'
  | 'tag'
  | 'refresh'
  | 'chart'
  | 'layers'
  | 'logout'
  | 'menu'
  | 'home'

withDefaults(
  defineProps<{
    name: IconName
    filled?: boolean
  }>(),
  {
    filled: false,
  },
)
</script>

<template>
  <svg
    class="base-icon"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    :fill="filled ? 'currentColor' : 'none'"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <template v-if="name === 'bag'">
      <path d="M6 8h12l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z" />
      <path d="M9 8V6a3 3 0 0 1 6 0v2" />
    </template>

    <template v-else-if="name === 'search'">
      <circle cx="11" cy="11" r="7" />
      <path d="m20 20-3.5-3.5" />
    </template>

    <template v-else-if="name === 'user'">
      <circle cx="12" cy="8" r="4" />
      <path d="M5 20a7 7 0 0 1 14 0" />
    </template>

    <template v-else-if="name === 'users'">
      <circle cx="9" cy="8" r="3.5" />
      <path d="M3 19.5a6 6 0 0 1 12 0" />
      <circle cx="17" cy="9" r="2.5" />
      <path d="M16 19.5h5a4.5 4.5 0 0 0-4.5-4.5" />
    </template>

    <template v-else-if="name === 'shield'">
      <path d="M12 3 5 6v6c0 4.5 3 7.8 7 9 4-1.2 7-4.5 7-9V6l-7-3Z" />
      <path d="m9.5 12 1.8 1.8 3.4-3.6" />
    </template>

    <template v-else-if="name === 'check'">
      <path d="M20 6 9 17l-5-5" />
    </template>

    <template v-else-if="name === 'check-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="m8.5 12.2 2.3 2.3 4.8-5" />
    </template>

    <template v-else-if="name === 'alert'">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 8v4.5" />
      <path d="M12 16h.01" />
    </template>

    <template v-else-if="name === 'x'">
      <path d="M18 6 6 18" />
      <path d="m6 6 12 12" />
    </template>

    <template v-else-if="name === 'plus'">
      <path d="M12 5v14" />
      <path d="M5 12h14" />
    </template>

    <template v-else-if="name === 'minus'">
      <path d="M5 12h14" />
    </template>

    <template v-else-if="name === 'trash'">
      <path d="M4 7h16" />
      <path d="M9 7V4h6v3" />
      <path d="m6 7 1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
    </template>

    <template v-else-if="name === 'arrow-right'">
      <path d="M5 12h14" />
      <path d="m13 6 6 6-6 6" />
    </template>

    <template v-else-if="name === 'arrow-left'">
      <path d="M19 12H5" />
      <path d="m11 6-6 6 6 6" />
    </template>

    <template v-else-if="name === 'chevron-right'">
      <path d="m9 6 6 6-6 6" />
    </template>

    <template v-else-if="name === 'chevron-left'">
      <path d="m15 6-6 6 6 6" />
    </template>

    <template v-else-if="name === 'star'">
      <polygon points="12 3.5 14.7 9 20.8 9.9 16.4 14.1 17.4 20.2 12 17.3 6.6 20.2 7.6 14.1 3.2 9.9 9.3 9" />
    </template>

    <template v-else-if="name === 'package'">
      <path d="M12 3 4 7.5v9L12 21l8-4.5v-9L12 3Z" />
      <path d="m4 7.5 8 4.5 8-4.5" />
      <path d="M12 12v9" />
    </template>

    <template v-else-if="name === 'truck'">
      <rect x="2" y="6" width="13" height="10" rx="1" />
      <path d="M15 9h4l3 3v4h-7V9Z" />
      <circle cx="7" cy="18" r="2" />
      <circle cx="18" cy="18" r="2" />
    </template>

    <template v-else-if="name === 'credit-card'">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="M3 10h18" />
      <path d="M7 15h4" />
    </template>

    <template v-else-if="name === 'qr'">
      <rect x="4" y="4" width="6" height="6" rx="1" />
      <rect x="14" y="4" width="6" height="6" rx="1" />
      <rect x="4" y="14" width="6" height="6" rx="1" />
      <path d="M14 14h3v3h-3z" />
      <path d="M19 14v6h-5" />
    </template>

    <template v-else-if="name === 'tag'">
      <path d="M12 3H5a2 2 0 0 0-2 2v7l9 9 9-9-9-9Z" />
      <circle cx="7.5" cy="7.5" r="1" />
    </template>

    <template v-else-if="name === 'refresh'">
      <path d="M20 11a8 8 0 0 0-14.5-4.5L4 8" />
      <path d="M4 4v4h4" />
      <path d="M4 13a8 8 0 0 0 14.5 4.5L20 16" />
      <path d="M20 20v-4h-4" />
    </template>

    <template v-else-if="name === 'chart'">
      <path d="M4 19h16" />
      <path d="M7 16v-5" />
      <path d="M12 16V8" />
      <path d="M17 16v-3" />
    </template>

    <template v-else-if="name === 'layers'">
      <polygon points="12 3 3 8 12 13 21 8 12 3" />
      <path d="m3 12.5 9 5 9-5" />
      <path d="m3 17 9 5 9-5" />
    </template>

    <template v-else-if="name === 'logout'">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
      <path d="m16 17 5-5-5-5" />
      <path d="M21 12H9" />
    </template>

    <template v-else-if="name === 'menu'">
      <path d="M4 7h16" />
      <path d="M4 12h16" />
      <path d="M4 17h16" />
    </template>

    <template v-else-if="name === 'home'">
      <path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9Z" />
      <path d="M9 21v-6h6v6" />
    </template>
  </svg>
</template>

<style scoped>
.base-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}
</style>
