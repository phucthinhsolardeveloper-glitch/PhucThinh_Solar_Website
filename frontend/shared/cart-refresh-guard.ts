export function isCurrentCartRead(
  readRevision: number,
  currentRevision: number,
  readSequence: number,
  latestSequence: number,
): boolean {
  return readRevision === currentRevision && readSequence === latestSequence
}
