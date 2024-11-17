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
