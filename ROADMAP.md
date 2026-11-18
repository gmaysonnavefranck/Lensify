# Lensify Roadmap

Build your own annotated edition of anything you read.

Sizes: **S** = hours, **M** = a day or two, **L** = several days.

## Done

- [x] Footnotes: create by hand or with AI, numbered markers in the text, hover/click linking, edit and delete, saved instantly
- [x] Texts: add with AI detection of title/author/language, edit details, delete
- [x] Authors: add with AI profile, chips for genres/themes/works, edit, delete
- [x] Quotes: save passages, list, search, copy, delete
- [x] Vocabulary: save words with AI definitions in context, list, search, edit, delete
- [x] Landing page, restyled login/sign-up, account menu with log out
- [x] AI on `gpt-5.4-mini` (configurable via `OPENAI_MODEL`), friendly errors when the AI fails
- [x] Security: users only see their own data, AI routes require login, leaked keys removed from git history
- [x] UI pass: compact sizing, full-page scrolling lists, popup kept inside the viewport
- [x] Books: EPUB and TXT import split into chapters, chapter navigation, continue where you left off
- [x] Paged reading: A4-like pages, text size control, keyboard page turns, remembers the reading point
- [x] Book glossary: characters, places and terms highlighted in the text with descriptions on hover
- [x] Highlights: marker-pen highlights in four colors, no note needed, saved instantly
- [x] Search: whole-book search (10 hits per page, snippets, opens on the right page) and find in chapter (Ctrl+F)
- [x] Home dashboard: continue reading, streak, pages and time per day with a 14-day chart, quote and word of the day, library totals

## Now: before launch

- [ ] **Onboarding** (S): new accounts start with a sample annotated text so the value is clear in seconds
- [ ] **Mobile layout** (M): text page stacks notes under the text; lists and modals work on phones
- [ ] **Usage limits** (M): free tier with a monthly AI quota, counter shown to the user
- [ ] **Payments** (M): Pro plan via Paddle or Lemon Squeezy (merchant of record handles foreign taxes)
- [ ] **Production setup** (M): Forge deploy, real email sending, database backups, queue worker
- [ ] **Legal** (S): privacy policy and terms (LGPD/GDPR), cookie notice if analytics are added

## Next: reading

- [ ] **Ask about a passage** (S): select text and type your own question ("is Sancho Panza's name about his belly?"); the AI answers using the passage and book, and the answer can be kept as a footnote. Covers what a generic Analyze would never guess
- [ ] **"Previously on…" recap** (M): AI summary of what you read so far when you come back to a book, built only from chapters before your reading point (no spoilers)
- [ ] **Glossary mentions** (S/M): where each name appears, located finer than a chapter (page or percentage), first appearance first
- [ ] **Book recommendations** (S): AI suggests a next book from recent books and authors, cached daily, shown on the dashboard

## Quality

- [ ] **Browser tests** (M): upgrade to Pest 4 and cover 6–8 critical flows plus screenshot checks of key pages
- [ ] **CI** (S): run the test suite on every push (GitHub Actions)
- [ ] **Error monitoring** (S): Sentry free tier in production
- [ ] **Faster local dev** (S): move the project into the WSL filesystem
- [ ] **Type errors** (S): `vue-tsc` reports old errors in files outside the recent work; clear them so type checks can gate CI

## Next: growth

- [ ] **Public annotated pages** (M): public-domain texts with footnotes as SEO landing pages
- [ ] **Share quote as image** (S): a nice card (serif quote, author, small logo) for Instagram/BookTok
- [ ] **Export** (M): download the annotated edition as PDF or Markdown, a natural Pro feature
- [ ] **Global search** (M): one search across texts, footnotes, quotes and vocabulary
- [ ] **Side-by-side translations** (L): original and translation in two columns, annotate both
- [ ] **PDF upload** (L): import PDF books into chapters

## Ideas to explore

- **Vocabulary flashcards** (M): spaced-repetition review of saved words
- **Ask the text** (M): chat with AI grounded only in the current text and its notes
- **One-click annotation** (S): AI drafts footnotes for a whole poem at once, user keeps what they like
- **Footnote types** (S): tag notes as context, literary device, translation, personal; filter by type
- **Relationship map** (L): visual map of how glossary characters connect
- **AI "Who is this?"** (S): draft a glossary description from the chapters read so far; skipped until spoilers can be avoided reliably
- **Collections / shelves** (S): group texts by course, project or theme
- **Import Kindle highlights** (M): turn existing highlights into texts with notes
- **Browser extension** (M): clip a passage from any site into Lensify
- **Shared editions for classes** (L): a teacher shares an annotated text, students add their own notes
- **Read aloud** (S): text-to-speech for the text and its footnotes
- **Portuguese interface** (M): PT-BR UI for the Brazilian student niche (ENEM reading lists)
- **Dark mode** (S)

## Known gaps

Deliberate shortcuts in what's built. Each is fine for now; the fix is noted for when it starts to matter. Most are also marked in the code with a `ponytail:` comment.

**Reader**
- A passage that runs onto the next page can't be selected whole. Workaround: lower the text size. Fix: select across pages.
- A note or highlight overlapping an earlier one isn't shown in the text (notes stay in the side panel). Fix: nest marks.
- Glossary names inside a footnoted passage aren't highlighted. Fix: nest glossary marks inside notes.
- A highlight made only of a glossary name can't be clicked to recolor or remove; the glossary opens instead. Workaround: highlight over it to replace it.
- Find in chapter marks at most 500 matches.
- The search-hit and find marks use the browser highlight API; very old browsers just jump to the page without marking.

**Search**
- Snippets need matching accents: "pequeno" won't find "pequeño". Fix: accent-insensitive matching.
- Book search scans chapter text in PHP. Fine for books; use a fulltext index if libraries get huge.

**Books**
- TXT import rejoins hard-wrapped lines by line length; poetry with long lines could get joined. Fix: an upload option to keep lines.
- Chapter numbers in search results use the stored position, which can skip a number after a chapter is deleted.

**Dashboard and stats**
- Page turns from the last ~3 seconds before closing the tab aren't counted. Fix: send with `fetch` keepalive on page hide.
- Streaks are counted from the last year of history, so they show at most 366 days. Fix: count on the server.
- History starts on 2026-11-18; Continue reading only knows texts opened since then.

**General**
- Character offsets assume no emoji or rare symbols: PHP counts characters, the browser counts UTF-16 units. Fine for book text.
