<template>
  <div class="modal" @click.self="$emit('close')">
    <div class="modal-dialog animated-scale">
      <div class="modal-content p-4">

        <div class="modal-header border-0 justify-content-center">
          <h4 class="inter-22 mb-0">Обріжте фото</h4>
        </div>

        <div class="modal-body overflow-hidden">
          <div class="cropper-wrapper rounded-4 overflow-hidden" style="height: 400px; background: #f8f8f8;">
            <Cropper
                ref="cropperRef"
                :src="imageToCrop"
                :stencil-props="{ aspectRatio: 1/1 }"
                class="h-100"
            />
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end gap-3 pt-3">
          <button class="btn btn-light rounded-4 px-4" @click="$emit('close')">Скасувати</button>
          <button class="orange-but py-2 px-4 text-white" @click="handleUpload" :disabled="updating">
            {{ updating ? 'Завантаження...' : 'Готово' }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({
  imageToCrop: { type: String, required: true },
  updating: { type: Boolean, default: false },
  masterId: { type: Number, required: true }
});

const emit = defineEmits(['close', 'success']);
const cropperRef = ref(null);

const handleUpload = async () => {
  const result = cropperRef.value.getResult();
  if (!result || !result.canvas) return;

  result.canvas.toBlob(async (blob) => {
    const formData = new FormData();
    formData.append('file', blob, 'avatar.jpg');

    try {
      const response = await fetch(`/api/v1/v1/masters/${props.masterId}/photo`, {
        method: 'POST',
        body: formData,
      });

      if (response.ok) {
        const data = await response.json();
        emit('success', data.photo);
      } else {
        alert('Не вдалося зберегти фото');
      }
    } catch (e) {
      console.error('Помилка завантаження:', e);
    }
  }, 'image/jpeg', 0.8);
};
</script>

<style lang="scss" scoped>
.orange-but {
  background: #FF9229;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  transition: 0.3s;
  &:disabled { opacity: 0.7; }
  &:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,146,41,0.4); }
}

.animated-scale {
  animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>