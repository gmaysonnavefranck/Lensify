# Lensify Roadmap

Build your own annotated edition of anything you read.

Sizes: **S** = hours, **M** = a day or two, **L** = several days.

## Done

- [x] Footnotes: create by hand or with AI, numbered markers in the text, hover/click linking, edit and delete
- [x] Texts: add with AI detection of title/author/language, edit details, delete
- [x] Authors: add with AI profile, chips for genres/themes/works, edit, delete
- [x] Quotes: save passages, list, search, copy, delete
- [x] Vocabulary: save words with AI definitions in context, list, search, edit, delete
- [x] Landing page, restyled login/sign-up, account menu with log out
- [x] AI on `gpt-5.4-mini` (configurable via `OPENAI_MODEL`), friendly errors when the AI fails
- [x] Security: users only see their own data, AI routes require login, leaked keys removed from git history
- [x] UI pass: compact sizing, full-page scrolling lists, popup kept inside the viewport

## Now: before launch

- [ ] **Onboarding** (S): new accounts start with a sample annotated text so the value is clear in seconds
- [ ] **Mobile layout** (M): text page stacks notes under the text; lists and modals work on phones
- [ ] **Usage limits** (M): free tier with a monthly AI quota, counter shown to the user
- [ ] **Payments** (M): Pro plan via Paddle or Lemon Squeezy (merchant of record handles foreign taxes)
- [ ] **Production setup** (M): Forge deploy, real email sending, database backups, queue worker
- [ ] **Legal** (S): privacy policy and terms (LGPD/GDPR), cookie notice if analytics are added

## Quality

- [ ] **Browser tests** (M): upgrade to Pest 4 and cover 6–8 critical flows plus screenshot checks of key pages
- [ ] **CI** (S): run the test suite on every push (GitHub Actions)
- [ ] **Error monitoring** (S): Sentry free tier in production
- [ ] **Faster local dev** (S): move the project into the WSL filesystem

## Next: growth

- [ ] **Public annotated pages** (M): public-domain texts with footnotes as SEO landing pages
- [ ] **Share quote as image** (S): a nice card (serif quote, author, small logo) for Instagram/BookTok
- [ ] **Export** (M): download the annotated edition as PDF or Markdown, a natural Pro feature
- [ ] **Global search** (M): one search across texts, footnotes, quotes and vocabulary
- [ ] **Side-by-side translations** (L): original and translation in two columns, annotate both
- [ ] **PDF upload / Books** (L): split long works into chapters; brings back the Books section

## Ideas to explore

- **Vocabulary flashcards** (M): spaced-repetition review of saved words
- **Ask the text** (M): chat with AI grounded only in the current text and its notes
- **One-click annotation** (S): AI drafts footnotes for a whole poem at once, user keeps what they like
- **Footnote types** (S): tag notes as context, literary device, translation, personal; filter by type
- **Characters and relationships** (L): character sheets per work and a relationship map
- **Collections / shelves** (S): group texts by course, project or theme
- **Import Kindle highlights** (M): turn existing highlights into texts with notes
- **Browser extension** (M): clip a passage from any site into Lensify
- **Shared editions for classes** (L): a teacher shares an annotated text, students add their own notes
- **Read aloud** (S): text-to-speech for the text and its footnotes
- **Portuguese interface** (M): PT-BR UI for the Brazilian student niche (ENEM reading lists)
- **Dark mode** (S)
