import { proxyBackendMedia } from '../../utils/media-proxy'

export default defineEventHandler((event) => proxyBackendMedia(event, `/upload/${getRouterParam(event, 'path') ?? ''}`))
