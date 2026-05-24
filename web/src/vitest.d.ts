/**
 * Type augmentation so TypeScript knows about jest-dom matchers
 * (toBeInTheDocument, toHaveTextContent, etc.) when used via Vitest's
 * `expect`. This file is picked up automatically by the tsconfig
 * `src/**\/*.ts` glob — no manual import needed in test files.
 *
 * The runtime side is wired up in `../vitest.setup.ts` via
 * `import '@testing-library/jest-dom/vitest';`.
 */
import 'vitest';
import type {TestingLibraryMatchers} from '@testing-library/jest-dom/matchers';

declare module 'vitest' {
    interface Assertion<T = unknown> extends TestingLibraryMatchers<T, void> {
    }

    interface AsymmetricMatchersContaining extends TestingLibraryMatchers<unknown, void> {
    }
}
