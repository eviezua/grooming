<template>
  <div class="search-wrapper search-cell" :class="{ expanded: isFocused }" ref="wrapper">
    <input
        type="search"
        v-model="searchText"
        class="search-input"
        placeholder="Знайти..."
    />
    <img
        src="/uploads/icons/search.png"
        alt="Search"
        class="search-icon"
        @click="toggleSearch"
    />
  </div>
</template>

<script>
export default {
  props: {
    modelValue: {
      type: String,
      default: ''
    }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      isFocused: false,
    }
  },
  computed: {
    searchText: {
      get() {
        return this.modelValue
      },
      set(val) {
        this.$emit('update:modelValue', val)
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  },
  methods: {
    toggleSearch() {
      this.isFocused = !this.isFocused
      if (this.isFocused) {
        this.$nextTick(() => this.$el.querySelector('input').focus())
      }
    },
    handleClickOutside(event) {
      if (this.isFocused && !this.$refs.wrapper.contains(event.target)) {
        this.isFocused = false
      }
    }
  }
}
</script>

<style>
.search-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 74px;
  border-radius: 100px;
  background-color: white;
  overflow: hidden;
  transition: width 0.4s ease, justify-content 0.4s ease;
  padding: 0 24px;
  width: 72px;
}

.search-wrapper.expanded {
  justify-content: space-between;
  position: absolute;
  inset: 0;
  z-index: 2500;
  box-shadow: 0 0 10px rgba(0,0,0,0.15);
  width: 100%;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-family: 'Inter', sans-serif;
  font-size: 18px;
  min-width: 0;
  opacity: 0;
  transition: opacity 0.4s ease;
}

.search-wrapper.expanded .search-input {
  opacity: 1;
}

.search-icon {
  width: 35px;
  height: 35px;
  cursor: pointer;
  flex-shrink: 0;
}

@media (max-width: 576px) {
  .search-wrapper.expanded {
    position: static !important;
    width: 100% !important;
  }
}
</style>
