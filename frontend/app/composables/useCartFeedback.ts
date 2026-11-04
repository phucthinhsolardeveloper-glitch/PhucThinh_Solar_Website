export interface CartFeedbackEvent {
  id: number
  name: string
  image: string
  quantity: number
  source: { x: number; y: number; width: number; height: number } | null
}

// One shared channel: a product panel announces an add, CartFeedback.vue plays the
// flight to the header cart and shows the confirmation card.
export function useCartFeedback() {
  const event = useState<CartFeedbackEvent | null>('cart-feedback-event', () => null)

  function celebrate(input: { name: string; image?: string; quantity: number; from?: Element | null }) {
    const rect = input.from?.getBoundingClientRect()
    event.value = {
      id: Date.now(),
      name: input.name,
      image: input.image || '',
      quantity: input.quantity,
      source: rect && rect.width ? { x: rect.x, y: rect.y, width: rect.width, height: rect.height } : null,
    }
  }

  return { event, celebrate }
}
