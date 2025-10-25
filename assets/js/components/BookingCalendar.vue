<template>
  <div class="scheduler-container">
    <div class="calendar">
      <VueDatePicker
          v-model="selectedDate"
          :enable-time-picker="false"
          :inline="true"
          @date-update="onSelectDate"
          :min-date="today"
          format="yyyy-MM-dd"
          :disabled-dates="isDisabledDate"
          auto-apply
      />
    </div>

    <div class="slots-container">
      <div class="slots-list">
        <div
            v-for="slot in timeSlots"
            :key="slot"
            class="slot"
            @click="selectTime(slot)"
            :class="{selected: slot === selectedTime}"
        >
          {{ slot }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, defineProps } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  schedules: {
    type: Array,
    default: () => []
  },
  bookings: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:selectedDate', 'update:selectedTime'])

const today = ref(new Date())

const selectedDate = ref(null)
const selectedTime = ref(null)

function getDayOfWeek(date) {
  return date.toLocaleDateString('en-US', { weekday: 'long' })
}

function isDisabledDate(date) {
  const dayName = getDayOfWeek(date)
  return !props.schedules.some(s => s.dayOfweek === dayName)
}

function onSelectDate(date) {
  selectedDate.value = date
  emit('update:selectedDate', date)
}

function generateSlots(startTime, stopTime) {
  const slots = []
  const [startH, startM] = startTime.split(':').map(Number)
  const [stopH, stopM] = stopTime.split(':').map(Number)

  let current = new Date()
  current.setHours(startH, startM, 0, 0)
  const end = new Date()
  end.setHours(stopH, stopM, 0, 0)

  while (current < end) {
    if (
        selectedDate.value?.toDateString() === today.value.toDateString() &&
        current < new Date()
    ) {
      current.setMinutes(current.getMinutes() + 30)
      continue
    }

    const slotStr = `${current.getHours().toString().padStart(2,'0')}:${current.getMinutes().toString().padStart(2,'0')}`

    const isBooked = props.bookings?.some(b => {
      const bookedStart = b.timeStart.slice(0,5)
      const bookedEnd = b.timeStop.slice(0,5)
      return slotStr >= bookedStart && slotStr < bookedEnd
    })

    if (!isBooked) slots.push(slotStr)
    current.setMinutes(current.getMinutes() + 30)
  }

  return slots
}

const timeSlots = computed(() => {
  if (!selectedDate.value || props.schedules.length === 0) return []

  const dayName = getDayOfWeek(selectedDate.value)
  const scheduleForDay = props.schedules.find(s => s.dayOfweek === dayName)
  if (!scheduleForDay) return []

  return generateSlots(scheduleForDay.start_time, scheduleForDay.stop_time)
})

function selectTime(slot) {
  selectedTime.value = slot
  emit('update:selectedTime', slot)
}

watch(selectedDate, async (newDate) => {
  selectedTime.value = null;
  await props.onDateChange?.(newDate);
});

</script>

<style>

.scheduler-container {
  display: grid;
  grid-template-columns: 1fr 180px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.calendar {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-radius: 8px;
  padding: 10px;
}

.slots-container {
  max-height: 385px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 10px;
  background-color: #fafafa;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.slots-container h5 {
  text-align: center;
  margin-bottom: 10px;
  color: #333;
}

.slots-list {
  display: grid;
  grid-template-rows: repeat(auto-fill, minmax(40px, 1fr));
  gap: 8px;
}

.slot {
  cursor: pointer;
  padding: 8px 10px;
  border-radius: 6px;
  text-align: center;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.slot:hover {
  background-color: #e0f0ff;
  border-color: #007bff;
}

.slot.selected {
  background-color: #007bff;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(0,123,255,0.4);
}

.selection {
  margin-top: 15px;
  font-size: 1rem;
  color: #333;
}
</style>