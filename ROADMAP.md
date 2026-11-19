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
- [x] Ask about a passage: select text, type your own question, AI answers with the surrounding text as context, keep it as a footnote
- [x] Dark mode: toggle in the header, follows the system setting until you pick one, no white flash on load
- [x] Privacy: deleting an account removes all of the user's data (foreign keys on every table), fonts are served from the app instead of Google

## Now: before launch

- [ ] **Onboarding** (S): new accounts start with a sample annotated text so the value is clear in seconds
- [ ] **Mobile layout** (M): text page stacks notes under the text; lists and modals work on phones
- [ ] **Usage limits** (M): free tier with a monthly AI quota, counter shown to the user
- [ ] **Payments** (M): Pro plan via Paddle or Lemon Squeezy (merchant of record handles foreign taxes)
- [ ] **Production setup** (M): Forge deploy, real email sending, database backups, queue worker
- [ ] **Legal** (S): privacy policy and terms (LGPD/GDPR), cookie notice if analytics are added

## Next: reading

- [ ] **"Previously on…" recap** (M): AI summary of what you read so far when you come back to a book, built only from chapters before your reading point (no spoilers)
- [ ] **Glossary mentions** (S/M): where each name appears, located finer than a chapter (page or percentage), first appearance first
- [ ] **Book recommendations** (S): AI suggests a next book from recent books and authors, cached daily, shown on the dashboard

## Quality

- [ ] **Browser tests** (M): upgrade to Pest 4 and cover 6–8 critical flows plus screenshot checks of key pages
- [ ] **CI** (S): run the test suite on every push (GitHub Actions)
- [ ] **Error monitoring** (S): Sentry free tier in production
- [ ] **Faster local dev** (S): move the project into the WSL filesystem
- [ ] **Type errors** (S): clear the old `vue-tsc` errors so type checks can gate CI

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

## Known gaps

Shortcuts and limits in what's built, collected from the code (`ponytail:` comments) and from building each feature. Each is fine for now unless marked **before launch**; the fix is noted for when it starts to matter.

**Reader**
- A passage that runs onto the next page can't be selected whole. Workaround: lower the text size. Fix: select across pages.
- A note or highlight overlapping an earlier one isn't shown in the text (notes stay in the side panel). Fix: nest marks.
- Glossary names inside a footnoted passage aren't highlighted. Fix: nest glossary marks inside notes.
- Glossary matching is case-sensitive: an entry "Sancho" doesn't highlight "SANCHO" in a chapter heading. Fix: match ignoring case.
- A highlight made only of a glossary name can't be clicked to recolor or remove; the glossary opens instead. Workaround: highlight over it to replace it.
- Adding or editing a glossary entry still reloads the page's data (the slow way highlights used to work). Fix: save in the background like highlights and notes.
- Opening a search result or jumping with find doesn't move the saved reading point; only turning pages does. Reloading the page after opening a search result jumps to the hit again, since it's in the URL.
- Ctrl+F opens the chapter find box, so the browser's own find isn't available on the reader page (it can't see hidden pages anyway).
- Find in chapter marks at most 500 matches.
- The search-hit and find marks use the browser highlight API; very old browsers just jump to the page without marking.
- Text content can't be edited after adding, because footnotes and highlights point at character positions in it.

**Search**
- Snippets need matching accents: "pequeno" won't find "pequeño". Fix: accent-insensitive matching.
- Book search scans chapter text in PHP. Fine for books; use a fulltext index if libraries get huge.

**Books**
- Import is text only: EPUB images, links and the book's own footnotes are dropped.
- Chapters are found from headings (EPUB h1 to h3) or, for TXT, lines starting with Chapter/Capítulo/Book/Livro/Part/Parte plus a number. Books that mark chapters differently come in as fewer, longer chapters.
- TXT import rejoins hard-wrapped lines by line length; poetry with long lines could get joined. Fix: an upload option to keep lines.
- Import runs inside the upload request. A very large book could hit the request timeout. Fix: a queued job with a progress state.
- The uploaded file isn't kept, only the parsed chapters, so a book can't be re-imported with a better parser later without uploading it again.
- Chapter numbers in search results use the stored position, which can skip a number after a chapter is deleted.
- Chapters can't be renamed or removed one by one (useful for junk sections like a copyright page that came in as a chapter). Fix: rename and remove actions on the book page's chapter list.

**Dashboard and stats**
- Reading time is the gap between page turns, capped at 5 minutes. A page read slowly for longer is undercounted, and time on the last page before leaving isn't counted.
- Page turns from the last ~3 seconds before closing the tab aren't counted. Fix: send with `fetch` keepalive on page hide.
- Streaks are counted from the last year of history, so they show at most 366 days. Fix: count on the server.
- History starts on 2026-11-18; Continue reading only knows texts opened since then.

**AI**
- Only the `/chat` routes are rate-limited (20 a minute). Word definitions, author lookup and text detection also call the AI and aren't limited. **Before launch**: the monthly AI quota, or the same throttle on those routes.
- Analyze sends only the selected words with a one-line prompt: no book, author or surrounding text, so its notes are generic. Ask has all of that. Fix: give Analyze the same context.
- Analyze and word definitions put instructions and the reader's text in one message. Ask keeps them apart (system message plus tagged user text). Fix: move them to the same pattern.
- Ask caps each answer at 1500 tokens, which includes the model's reasoning. Raise it if real answers come back cut off.

**Data and privacy**
- Footnote text and selected passages have no length limit on save.

**Code**
- `Button` only looks disabled when `disabled` is set; clicks still fire, so every handler has to check for itself. Fix: set the real `disabled` attribute in the component.
- `vue-tsc` reports old errors in files outside the recent work, so type checks can't gate CI yet.
- Some older endpoints answer someone else's text with 403 and JSON, the rest with 404. Harmless, just inconsistent.
- Character offsets assume no emoji or rare symbols: PHP counts characters, the browser counts UTF-16 units. Fine for book text.
- Dark mode works by swapping the gray scale and text colors; the landing, login and profile pages were not reviewed one by one, and a few small chips on the blue cards turn darker instead of staying light. A system theme change while the app is open applies on the next page load.

**Local development**
- Docker on a Windows drive doesn't pass file changes through: Vite polls for them, and Tailwind may miss classes in a brand-new file until Vite restarts.
- If Vite stops without cleaning up, a leftover `public/hot` file breaks the site's styles and scripts. Fix: delete it or start Vite again. Moving the project into WSL (see Quality) fixes both.
