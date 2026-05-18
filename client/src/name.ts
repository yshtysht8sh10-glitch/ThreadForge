export const MAX_NAME_LENGTH = 30;

export function composeUserName(displayName: string, suffix: string): string {
  const trimmed = suffix.trim();
  return trimmed === '' ? displayName : `${displayName}@${trimmed}`;
}

export function userNameSuffixLimit(displayName: string): number {
  return Math.max(0, MAX_NAME_LENGTH - displayName.length - 1);
}

export function clampUserNameSuffix(displayName: string, suffix: string): string {
  return suffix.slice(0, userNameSuffixLimit(displayName));
}
