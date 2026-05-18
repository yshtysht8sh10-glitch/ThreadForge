import { describe, expect, it } from 'vitest';
import { clampUserNameSuffix, composeUserName, userNameSuffixLimit } from './name';

describe('name helpers', () => {
  it('limits the short note so display name, at mark, and note fit in 30 chars', () => {
    expect(userNameSuffixLimit('Yes')).toBe(26);
    expect(clampUserNameSuffix('Yes', 'abcdefghijklmnopqrstuvwxyz012345')).toBe('abcdefghijklmnopqrstuvwxyz');
    expect(composeUserName('Yes', '作業中')).toBe('Yes@作業中');
    expect(composeUserName('Yes', '   ')).toBe('Yes');
  });
});
