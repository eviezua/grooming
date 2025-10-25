<template>
  <div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle inter-18 text-black d-flex justify-content-between align-items-center"
            type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="truncate">{{ selectedLabel || label }}</span>
      <img src="/uploads/icons/arrow_dropdown.png">
    </button>
    <div class="dropdown-menu">
      <ul class="dropdown-scrollable">
        <li class="search">
          <input
              type="search"
              placeholder="Знайти..."
              v-model="searchQuery"
          />
          <img
              src="/uploads/icons/search.png"
              alt="Search"
          />
        </li>
        <li>
          <a
              class="dropdown-item inter-18 truncate text-danger"
              @click="reset"
          >
            ✕ {{ 'Скинути' }}
          </a>
        </li>
        <li
            v-for="item in internalOptions"
            :key="item.id"
        >
          <a
              class="dropdown-item inter-18 truncate"
              @click="select(item)"
          >
            {{ item.name }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    options: {
      type: Array,
      required: true
    },
    label: {
      type: String,
      default: 'Вибір'
    },
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    resource: {
      type: String,
      default: null
    }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      searchQuery: '',
      internalOptions: []
    }
  },
  watch: {
    searchQuery(value) {
      if (value.length < 3 || !value) {
        this.internalOptions = [...this.options];
      } else {
        this.fetchFilteredOptions();
      }
    },
    options(newVal) {
      this.internalOptions = [...newVal];
    }
  },
  computed: {
    selectedLabel() {
      if (typeof this.modelValue === 'object') {
        return this.modelValue?.name || null;
      }
      const match = this.internalOptions.find(o => o.id === this.modelValue);
      return match?.name || null;
    }
  },
  methods: {
    select(item) {
      this.$emit('update:modelValue', item)
    },
    reset() {
      this.$emit('update:modelValue', null)
    },
    async fetchFilteredOptions() {
      if (!this.resource) return;

      const query = `/api/v1/${this.resource}?search=${encodeURIComponent(this.searchQuery)}`;

      try {
        const res = await fetch(query);
        const json = await res.json();
        const rawItems = json['hydra:member'] || json['member'] || json || [];

        this.internalOptions = rawItems.map(item => {
          if (this.resource === 'cities') {
            return { id: item.id, name: item.city };
          }
          if (this.resource === 'pets') {
            return { id: item.id, name: `${item.spice} — ${item.breed}` };
          }
          return { id: item.id, name: item.name };
        });
      } catch (err) {
        console.error('Failed to fetch filtered options', err);
      }
    }

  }
}
</script>

<style scoped>

.btn-secondary {
  border-radius: 100px !important;
  background-color: white !important;
  width: 100%;
  min-width: 0;
  padding: 20px 30px;
  border: none !important;
  box-shadow: none !important;
  color: black;
  z-index: 200;
  position: relative;

  &:hover,
  &:focus,
  &:active,
  &.show {
    background-color: #FF9229 !important;
    color: black !important;
    box-shadow: none !important;
    outline: none !important;
  }
}

.dropdown-toggle::after {
  display: none;
}

.dropdown-menu{
  z-index: 350 !important;
  width: 100% !important;
  max-height: 300px;
  padding: 0;
  overflow: hidden;
}

.dropdown-scrollable {
  list-style: none;
  padding: 0;
  max-height: 300px;
  overflow-y: auto;
  overflow-x: hidden;
}

.search {
  z-index: 10;
  background-color: white;
  position: sticky;
  width: 95%;
  align-items: center;
  margin-inline: auto;
  display: flex;
  top: 0;
  input{
    width:  100%;
  }
  img{
    width: 30px;
    border: 1px black solid;
    padding: 4px;
  }
}

.dropdown-item {
  transition: background-color 0.2s ease;
  position: relative;

  &:hover,
  &:focus,
  &:active,
  &.active {
    background-color: #FF9229 !important;
    color: black !important;
  }
}
</style>