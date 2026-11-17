<script setup lang="ts">
import { onMounted } from 'vue'
import ToastContainer from './components/common/ToastContainer.vue'
import { useAuthStore } from './stores/auth.store'

const authStore = useAuthStore()

onMounted(() => {
  if (authStore.token && !authStore.initialized) {
    void authStore.fetchMe()
  }
})
</script>

<template>
  <RouterView />
  <ToastContainer />
</template>

<style>
/* Hallmark: CleanCommerce Calm Daylight Enterprise Architecture */
.app-root-anchor {
  min-height: 100svh;
}
</style>
