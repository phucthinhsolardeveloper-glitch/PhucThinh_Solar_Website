import { ORDER_SUCCESS_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.orderSuccessPublicRouteEnabled)) return
  const token = typeof to.query.token === 'string' ? to.query.token : ''
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, ORDER_SUCCESS_PUBLIC_PATH, { token }), {
    external: true,
    redirectCode: 307,
  })
})
