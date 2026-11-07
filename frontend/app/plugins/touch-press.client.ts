// Safari on iPhone only applies :active while a touch listener exists on the page, so without this the press
// feedback on buttons (scale and shade in main.css) never shows there.
export default defineNuxtPlugin(() => {
  document.addEventListener('touchstart', () => {}, { passive: true })
})
