-- Allow cash_box withdrawals even when balance is insufficient (negative balance allowed).
DROP TRIGGER IF EXISTS `trg_before_withdraw_cash_box`;
