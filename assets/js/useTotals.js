import { computed } from "vue";

export function useTotals(selectedServices, selectedBreed) {
    const totals = computed(() => {
        let base = 0;
        let master = 0;
        let minutes = 0;

        for (const s of selectedServices.value) {
            base += s.cost || 0;
            master += s.master_price ?? s.cost ?? 0;

            if (s.default_time) {
                const [h, m] = s.default_time.split(':').map(Number);
                minutes += h * 60 + m;
            }
        }

        const coef = selectedBreed.value?.cost_coficient ?? 1;

        const totalCost = base * coef;
        const totalMasterCost = master * coef;

        const hours = Math.floor(minutes / 60);
        const restMin = minutes % 60;
        const formattedTime = `${hours} год ${restMin.toString().padStart(2, '0')} хв`;

        return { totalCost, totalMasterCost, minutes, formattedTime };
    });

    const totalCost = computed(() => totals.value.totalCost);
    const totalMasterCost = computed(() => totals.value.totalMasterCost);
    const totalMinutes = computed(() => totals.value.minutes);
    const totalTime = computed(() => totals.value.formattedTime);

    return { totals, totalCost, totalMasterCost, totalMinutes, totalTime };
}
