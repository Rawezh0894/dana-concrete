-- Add NORMAL and SOFT to concrete_formulas.type enum
ALTER TABLE `concrete_formulas`
  MODIFY COLUMN `type` ENUM(
    'عەرزی تێکەڵ',
    'عەرزی سادە',
    'سەقف',
    'پایە',
    'NORMAL',
    'SOFT'
  ) NOT NULL;
