<script setup lang="ts">
withDefaults(
  defineProps<{
    id: string
    modelValue: string | number
    label?: string
    type?: string
    placeholder?: string
    hint?: string
    error?: string | null
    required?: boolean
    disabled?: boolean
    autocomplete?: string
    mono?: boolean
    tabular?: boolean
  }>(),
  {
    label: undefined,
    type: 'text',
    placeholder: '',
    hint: undefined,
    error: null,
    required: false,
    disabled: false,
    autocomplete: undefined,
    mono: false,
    tabular: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="field">
    <label v-if="label" :for="id" class="field-label">{{ label }}</label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :autocomplete="autocomplete"
      class="input"
      :class="{ 'mono-code': mono, tabular: tabular, 'has-error': Boolean(error) }"
      @input="onInput"
    />
    <p v-if="error" class="field-error">{{ error }}</p>
    <p v-else-if="hint" class="field-hint">{{ hint }}</p>
  </div>
</template>

<style scoped>
.has-error {
  border-color: var(--color-danger);
}
</style>
