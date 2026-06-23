-- Add ئەساس to concrete_formulas.type enum
ALTER TABLE `concrete_formulas`
  MODIFY COLUMN `type` ENUM(
    'عەرزی تێکەڵ',
    'عەرزی سادە',
    'سەقف',
    'پایە',
    'ئەساس',
    'NORMAL',
    'SOFT'
  ) NOT NULL;
