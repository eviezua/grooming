<template>
  <div
      class="faq-block w-100 justify-content-between container-wide"
      :class="[bgClass, { 'faq-open': isOpen }]"
  >
    <p class="inter-22 question truncate">{{ question }}</p>

    <div
        v-if="showAnswer"
        ref="answerWrapper"
        class="answer-wrapper"
        :class="{ visible: isFullyVisible }"
    >
      <p class="inter-18 answer">{{ answer }}</p>
    </div>

    <div @click="toggleOpen" style="cursor: pointer;">
      <img
          v-if="!isOpen"
          :src="icon_default"
          alt="FAQ icon default"
      />
      <img
          v-else
          :src="icon_hover"
          alt="FAQ icon hover"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'FAQItem',
  props: {
    question: { type: String, required: true },
    answer: { type: String, required: true },
    icon_default: { type: String, required: true },
    icon_hover: { type: String, required: true },
    index: { type: Number, required: true }
  },
  data() {
    return {
      isOpen: false,
      showAnswer: false,
      isFullyVisible: false
    };
  },
  computed: {
    bgClass() {
      return this.index % 2 === 0 ? 'faq-even' : 'faq-odd';
    }
  },
  methods: {
    toggleOpen() {
      if (!this.isOpen) {
        this.showAnswer = true;
        this.$nextTick(() => {
          setTimeout(() => {
            this.isFullyVisible = true;
          }, 10);
        });
      } else {
        this.isFullyVisible = false;
        setTimeout(() => {
          this.showAnswer = false;
        }, 300);
      }
      this.isOpen = !this.isOpen;
    }
  }
};
</script>

<style scoped>
p {
  margin: auto clamp(5px, 2.5vw, 35px);
}

.faq-open .question {
  white-space: normal;
}

.faq-open p {
  margin: clamp(5px, 2.5vw, 35px);
}

.faq-block {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  grid-template-rows: auto;
  align-items: center;
  border-radius: clamp(20px, 4vw, 50px);
  padding: clamp(5px, 2vw, 15px);
  transition: background-color 0.3s, color 0.3s;

  img {
    width: clamp(30px, 5vw, 77px);
    height: clamp(30px, 5vw, 77px);
  }
}

.question {
  grid-column: 1;
  grid-row: 1;
  min-width: 0;
}

.answer-wrapper {
  grid-column: 2;
  grid-row: 1;
  opacity: 0;
  max-height: 0;
  overflow: hidden;
  transition: opacity 0.6s ease, max-height 0.6s ease;
  pointer-events: none;
}

.answer-wrapper.visible {
  opacity: 1;
  max-height: 500px;
  pointer-events: auto;
}

.answer {
  max-width: 574px;
  margin: 0;
}

.faq-block > div[style] {
  grid-column: 3;
  grid-row: 1;
}

@media (max-width: 991px) {
  .faq-block {
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto;
  }

  .question {
    grid-column: 1;
    grid-row: 1;
  }

  .faq-block > div[style] {
    grid-column: 2;
    grid-row: 1;
  }

  .answer-wrapper {
    grid-column: 1 / -1;
    grid-row: 2;
    max-width: 574px;
    margin: 0 clamp(5px, 2.5vw, 35px);
  }
}

.faq-open .question {
  max-width: 679px;
}

.faq-even {
  background-color: white;
  color: black;
}

.faq-odd {
  background-color: #FF9229;
  color: white;
}

</style>