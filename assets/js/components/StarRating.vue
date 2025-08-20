<template>
  <div class="star-rating">
    <span
        v-for="star in max"
        :key="star"
        class="star"
        :class="{ filled: star <= currentRating }"
        @click="setRating(star)"
    >
      <svg viewBox="0 0 20 20" class="star-svg">
        <path d="M9.5 14.25l-5.584 2.936 1.066-6.218L.465 6.564l6.243-.907L9.5 0l2.792 5.657 6.243.907-4.517 4.404 1.066 6.218Z"/>
      </svg>
    </span>
  </div>
</template>

<script>
export default {
  props: {
    max: { type: Number, default: 5 },
    modelValue: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false }
  },
  computed: {
    currentRating() {
      return this.modelValue;
    }
  },
  methods: {
    setRating(value) {
      if (!this.readonly) this.$emit('update:modelValue', value);
    }
  }
};
</script>

<style scoped>
.star-rating {
  display: flex;
  cursor: pointer;
  gap: 8px;
}

.star {
  width: clamp(25px, 2vw, 30px);
  height: clamp(25px, 2vw, 30px);
  transition: color 0.2s;
  color: #D9D9D9;
}

.star.filled {
  color: #FF9229;
}

.star-svg {
  width: 100%;
  height: 100%;
  fill: currentColor;
  display: block;
}
</style>
