<template>
  <div class="hero-master bg-white position-relative p-5 my-5">
    <div class="d-flex align-items-center mt-3 gap-2">
      <h3 class="mb-0">{{ $t('Rating:') }}</h3>
      <star-rating :model-value="groomer.rating" :readonly="true"></star-rating>
      <div class="circle-block d-flex position-absolute">
        <img :src="`/uploads/photos/${groomer.photo}`" :alt="groomer.name">
      </div>
    </div>
    <h2 class="mb-2 mt-4">{{ groomer.name }} {{ groomer.surname }}</h2>
    <h3 class="gray-text">{{ groomer.city }}</h3>
    <h3 class="my-4">Стрижка собаки до 5кг — {{ groomer.price }} грн</h3>
    <a class="mb-3">Обрати час <img src="/uploads/icons/arrow_black.png"></a>
  </div>
</template>

<script>
export default {
  props: {
    groomer: {
      type: Object,
      required: true
    }
  }
}
</script>

<style>

</style>