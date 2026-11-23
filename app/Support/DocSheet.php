<?php

namespace App\Support;

/**
 * The look of filling in a form on the page itself (<x-doc.sheet>): a blank line to type on, the small
 * caption under it (as printed forms have), a value already printed, and ruled lines for longer answers.
 * Student forms, paper forms, the issue page and signers' answers all use these, so they read the same.
 */
final class DocSheet
{
    /** A blank line: the wrapper. Its input uses INPUT. Add ERROR_LINE when the answer has a problem. */
    public const LINE = 'relative flex min-w-0 items-center gap-2 border-b border-slate-400 transition-colors focus-within:border-[#2A57B4] focus-within:bg-[#2A57B4]/[0.04] hover:border-slate-500';

    public const ERROR_LINE = '!border-rose-500 bg-rose-50/40';

    /** The input on a blank line: no box, just the typed text. */
    public const INPUT = 'w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-base font-medium text-slate-900 placeholder:font-normal placeholder:text-slate-300 outline-none focus:outline-none focus:ring-0 sm:text-[15px]';

    /** Ruled lines for a longer answer (a textarea): the lines sit under the text, 28px apart. */
    public const LINED = 'block w-full min-w-0 resize-y border-0 bg-transparent px-0.5 py-0 text-base font-medium leading-7 text-slate-900 placeholder:font-normal placeholder:text-slate-300 outline-none focus:outline-none focus:ring-0 sm:text-[15px] bg-[linear-gradient(to_bottom,transparent_27px,#94a3b8_27px,#94a3b8_28px)] bg-[length:100%_28px] bg-local focus:bg-[linear-gradient(to_bottom,transparent_27px,#2A57B4_27px,#2A57B4_28px)]';

    /**
     * A blank line on its own (no wrapper): for an <input>, or a <textarea>, which then gets ruled lines.
     * Layouts with their own labels (issue-paper/*) use this as their $input.
     */
    public const FIELD = 'mt-1 block w-full min-w-0 border-0 border-b border-slate-400 bg-transparent px-0.5 py-1.5 text-base font-medium text-slate-900 placeholder:font-normal placeholder:text-slate-300 outline-none transition-colors hover:border-slate-500 focus:border-[#2A57B4] focus:bg-[#2A57B4]/[0.04] focus:outline-none focus:ring-0 sm:text-[15px] [&:is(textarea)]:resize-y [&:is(textarea)]:border-b-0 [&:is(textarea)]:py-0 [&:is(textarea)]:leading-7 [&:is(textarea)]:bg-[linear-gradient(to_bottom,transparent_27px,#94a3b8_27px,#94a3b8_28px)] [&:is(textarea)]:bg-[length:100%_28px] [&:is(textarea)]:bg-local [&:is(select)]:pr-8';

    /** The caption under a line: what goes there, small and italic like the printed form. */
    public const CAPTION = 'mt-1 block text-[11px] italic leading-snug text-slate-500';

    /** A value already on the form (from the account, or the answer once sent). */
    public const VALUE = 'min-h-[2.25rem] whitespace-pre-wrap break-words border-b border-slate-300 px-0.5 py-1.5 text-base font-medium text-slate-900 sm:text-[15px]';

    /** A short note that only shows while its line is being filled in (the field's wrapper needs "group/f"). */
    public const HINT = 'mt-1 hidden text-xs leading-relaxed text-slate-500 group-focus-within/f:block';

    public const ERROR = 'mt-1 flex items-start gap-1 text-xs font-medium text-rose-600';

    /** A section heading on the sheet, e.g. "Signatures". */
    public const HEADING = 'text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500';

    /** A tick box or a choice, as on paper: a row you tap. */
    public const OPTION = 'flex min-h-11 cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-[15px] text-slate-800 transition-colors hover:bg-slate-50 has-[:checked]:font-semibold has-[:checked]:text-slate-900 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2A57B4]/40';
}
