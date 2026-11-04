/**
 * One look for every module's card view, with Components/RecordCard.vue and CardField.vue:
 * the header says what it is, the subject is what you read first, then the tags, the key figures and the other details.
 */

/** The cards' grid */
export const CARD_GRID = 'grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3';

/** The main thing on the card (destination, employee, title), read first */
export const CARD_SUBJECT = 'break-words text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100';

/** Shaded panel for the key figures (times, days); add grid-cols-2 or grid-cols-3 */
export const CARD_STATS = 'grid gap-2 rounded-lg border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-900/50';

/** A row of tags */
export const CARD_TAGS = 'flex flex-wrap gap-1.5';

/** Round picture of a person's initials, in place of the card's icon */
export const CARD_AVATAR = 'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white dark:bg-emerald-700';
