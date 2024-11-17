ssh Lensify
sail up
sail artisan migrate:fresh --seed
sail npm run dev
sail npm run build

sail artisan make:model 'modelName' -mfr

# Lensify

## Nouns/Resources

- **User**
- **Authors**
- **Texts**
- **TextNotes**

Authors
-listing screen
-listing endpoint
-filter name, theme, genre, nationality and literary period
-listing theme
-listing genre
-listing nationality
-listing literary period
-creating screen
-fetch author information from AI
-create author
-view/delete/editing screen
-fetch author by ID
-edit author
-delete author
