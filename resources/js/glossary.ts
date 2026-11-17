import { GlossaryType } from '@/types';

export const GLOSSARY_TYPES: Record<GlossaryType, { label: string; icon: string }> = {
  character: { label: 'Character', icon: 'author' },
  place: { label: 'Place', icon: 'globe' },
  other: { label: 'Other', icon: 'info' },
};
