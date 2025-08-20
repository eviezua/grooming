<template>
  <div class="d-flex gap-4 justify-content-center flex-wrap my-5 position-relative">
    <FAQItem
        v-for="(item, i) in filteredFaqList"
        :key="i"
        :question="item.question"
        :answer="item.answer"
        :icon_default="item.icon_default"
        :icon_hover="item.icon_hover"
        :index="i"
    />
  </div>
</template>
<script>
import FAQItem from './FAQItem.vue'

export default {
  name: 'FAQList',
  components: { FAQItem },
  props: {
    limit: {
      type: [Number, String],
      default: null,
    }
  },
  data() {
    return {
      faqList: [
        {
          question: 'Як часто потрібно стригти мого кота/собаку?',
          answer: 'Частота стрижки залежить від породи та типу шерсті. Зазвичай собаки потребують стрижки раз на 4-8 тижнів, а коти — рідше, в залежності від довжини шерсті. Цуценятам і кошенятам зазвичай потрібно більше часу для адаптації до процедур.',
          icon_default: '/uploads/icons/button_white_black_down.png',
          icon_hover: '/uploads/icons/button_white_black_up.png'
        },
        {
          question: 'Чи можна мити кота/собаку перед стрижкою?',
          answer: 'Мити собаку або кота перед стрижкою не тільки можна, \n' +
              'а й рекомендується! Чисту тварину буде легше стригти, \n' +
              'і результат буде кращим. Якщо у вас мало досвіду в привченні тварини до води, то краще довірити цей процес грумеру. \n' +
              'Він не лише акуратно вимие вашого улюбленця, а й дасть корисні поради щодо того, як правильно мити тварину вдома. ',
          icon_default: '/uploads/icons/button_white_orange_down.png',
          icon_hover: '/uploads/icons/button_white_orange_up.png'
        },
        {
          question: 'Чи можна вам довірити стрижку моїх гризунів або папуг?',
          answer: 'Гризунам зазвичай не потрібно стригти шерсть, але ми допомагаємо з доглядом за їх кігтями, щоб вони не виростали занадто довгими і не завдавали дискомфорту. Папугам також необхідно стригти кігті, щоб вони не стали занадто довгими і не викликали проблем із здоров\'ям чи поведінкою.\n',
          icon_default: '/uploads/icons/button_white_black_down.png',
          icon_hover: '/uploads/icons/button_white_black_up.png'
        },
        {
          question: 'Що робити, якщо мій пес або кіт боїться води?',
          answer: 'Ми застосовуємо методи, які допомагають зменшити стрес у тварин. Для собак і котів, які не люблять воду, ми використовуємо спеціальні засоби та підхід, щоб зробити процес комфортним для вашого улюбленця.',
          icon_default: '/uploads/icons/button_white_orange_down.png',
          icon_hover: '/uploads/icons/button_white_orange_up.png'
        },
        {
          question: 'Як мені дізнатися, чи оброблено мою заявку?',
          answer: 'Після подачі заявки ви залишаєте свої контактні дані, і наші грумери зв\'яжуться з вами для підтвердження бронювання та уточнення деталей. Ви завжди отримаєте повідомлення про статус вашої заявки.',
          icon_default: '/uploads/icons/button_white_black_down.png',
          icon_hover: '/uploads/icons/button_white_black_up.png'
        },
        {
          question: 'Як я можу зв\'язатися з грумером?',
          answer: 'Наша платформа виступає посередником між вами та грумером. Якщо ви хочете зв\'язатися з конкретним грумером — після обробки вашої заявки ми надішлемо вам контактні дані, і ви зможете зв\'язатися для уточнення деталей або підтвердження бронювання, якщо є бажання, це необов\'язково.',
          icon_default: '/uploads/icons/button_white_orange_down.png',
          icon_hover: '/uploads/icons/button_white_orange_up.png'
        },
        {
          question: 'Моя собака сильно линяє, що ви можете з цим зробити?',
          answer: 'Ми допомагаємо зменшити линяння у собак завдяки правильному догляду за шерстю та спеціальним процедури. Це також стосується котів та інших тварин, якщо у них є надмірне линяння.',
          icon_default: '/uploads/icons/button_white_black_down.png',
          icon_hover: '/uploads/icons/button_white_black_up.png'
        },
        {
          question: 'Навіщо ця платформа?',
          answer: 'Наша платформа створена, щоб спростити процес пошуку і бронювання послуг для грумерів і їх клієнтів. Це зручний спосіб знайти потрібного фахівця або салон і швидко забронювати час для вашого улюбленця.',
          icon_default: '/uploads/icons/button_white_orange_down.png',
          icon_hover: '/uploads/icons/button_white_orange_up.png'
        },
        {
          question: 'Чи використовуєте ви лише органічні засоби для догляду за моєю твариною?',
          answer: 'Ми працюємо з високоякісними, безпечними засобами для догляду за вашими тваринами. Це стосується не тільки собак і котів, але й гризунів, папуг та інших тварин, яких ми обслуговуємо.',
          icon_default: '/uploads/icons/button_white_black_down.png',
          icon_hover: '/uploads/icons/button_white_black_up.png'
        },
        {
          question: 'Чи можна записатися на стрижку без попереднього вибору часу?',
          answer: 'У нас онлайн платформа для зручного запису, і ми рекомендуємо обов\'язково вибирати зручний для вас час. Це дозволяє уникнути черг та забезпечити найбільш комфортні умови для вас і вашого улюбленця. Ви можете обрати зручний слот і записатися на послугу без зайвих зусиль.\n' +
              ' папугами та іншими тваринами ми рекомендуємо дати час на адаптацію, якщо це необхідно.\n',
          icon_default: '/uploads/icons/button_white_orange_down.png',
          icon_hover: '/uploads/icons/button_white_orange_up.png'
        }
      ]
    }
  },
  computed: {
    filteredFaqList() {
      if (this.limit !== null && this.limit !== undefined) {
        const lim = Number(this.limit)
        if (!isNaN(lim) && lim > 0) {
          return this.faqList.slice(0, lim)
        }
      }
      return this.faqList
    }
  }
}
</script>