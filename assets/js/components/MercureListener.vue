<script setup>
import { onMounted } from 'vue'
import { useMercure } from '../useMercure'

const props = defineProps({
  topic: { type: String, required: true }
})

const reloadCrudTable = async () => {
  const url = window.location.href;
  const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  if (!response.ok) return;

  const html = await response.text();
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, 'text/html');
  const newContent = doc.querySelector('.ea-content') || doc.querySelector('.content-body');
  const container = document.querySelector('.ea-content') || document.querySelector('.content-body');

  if (newContent && container) {
    container.innerHTML = newContent.innerHTML;
    document.dispatchEvent(new CustomEvent('ea.page-loaded'));}
}

onMounted(() => {
  useMercure({
    topic: props.topic,
    onMessage: reloadCrudTable
  })
})
</script>

<template>
</template>