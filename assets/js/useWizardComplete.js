import { nextTick } from "vue";

export function useWizardComplete() {
    const runWizardComplete = async (callback) => {
        if (callback && typeof callback === "function") {
            const result = await callback();
            if (!result) return;
        }

        const progressBar = document.querySelector('.wizard-progress-bar');
        if (progressBar) {
            progressBar.style.width = '100%';
        }

        await nextTick();
        await new Promise(resolve => setTimeout(resolve, 400));
    };

    return { runWizardComplete };
}
