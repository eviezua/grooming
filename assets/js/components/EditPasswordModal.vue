<template>
  <div class="modal" @click.self="$emit('close')">
    <div class="modal-dialog animated-scale" style="max-width: 450px; height: auto;">
      <div class="modal-content p-4">

        <div class="modal-header border-0 justify-content-center pb-0">
          <h4 class="inter-22 mb-0">Безпека акаунту</h4>
        </div>

        <div class="modal-body py-4">
          <div class="d-flex flex-column gap-3">
            <div>
              <label class="gray-text small mb-1">Поточний пароль</label>
              <div class="position-relative">
                <input
                    v-model="passForm.oldPassword"
                    :type="showPass.old ? 'text' : 'password'"
                    class="form-control edit-input pe-5"
                    placeholder="Введіть старий пароль"
                >
                <button type="button" class="btn-eye" @click="showPass.old = !showPass.old">
                  {{ showPass.old ? '👁️' : '👁️‍🗨️' }}
                </button>
              </div>
            </div>

            <hr class="my-2" style="border-top: 1px dashed #ddd;">

            <div>
              <label class="gray-text small mb-1">Новий пароль</label>
              <div class="position-relative">
                <input
                    v-model="passForm.newPassword"
                    :type="showPass.new ? 'text' : 'password'"
                    class="form-control edit-input pe-5"
                    placeholder="Мінімум 6 символів"
                >
                <button type="button" class="btn-eye" @click="showPass.new = !showPass.new">
                  {{ showPass.new ? '👁️' : '👁️‍🗨️' }}
                </button>
              </div>
            </div>

            <div>
              <label class="gray-text small mb-1">Підтвердження</label>
              <div class="position-relative">
                <input
                    v-model="passForm.confirmPassword"
                    :type="showPass.confirm ? 'text' : 'password'"
                    class="form-control edit-input pe-5"
                    placeholder="Повторіть новий пароль"
                >
                <button type="button" class="btn-eye" @click="showPass.confirm = !showPass.confirm">
                  {{ showPass.confirm ? '👁️' : '👁️‍🗨️' }}
                </button>
              </div>
            </div>

            <div v-if="passError" class="text-danger small mt-1 animate__animated animate__headShake">
              {{ passError }}
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end gap-3 pt-0">
          <button class="btn btn-light rounded-4 px-4" @click="$emit('close')">Скасувати</button>
          <button class="orange-but py-2 px-4 text-white"
                  @click="handleSave"
                  :disabled="updating || !isPassFormValid">
            {{ updating ? 'Оновлюємо...' : 'Зберегти пароль' }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps(['updating']);
const emit = defineEmits(['close', 'save']);

const showPass = ref({ old: false, new: false, confirm: false });
const passError = ref('');
const passForm = ref({ oldPassword: '', newPassword: '', confirmPassword: '' });

const isPassFormValid = computed(() => {
  return passForm.value.oldPassword.length > 0 &&
      passForm.value.newPassword.length >= 6 &&
      passForm.value.newPassword === passForm.value.confirmPassword;
});

const handleSave = async () => {
  passError.value = '';
  if (passForm.value.newPassword !== passForm.value.confirmPassword) {
    passError.value = 'Нові паролі не збігаються';
    return;
  }
  emit('save', { ...passForm.value }, (err) => {
    passError.value = err;
  });
};
</script>

<style lang="scss" scoped>
.modal {
  z-index: 2100;
}

.position-relative .btn-eye {
  position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #888; cursor: pointer; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center;
}

.edit-input.pe-5 { padding-right: 45px !important; }

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