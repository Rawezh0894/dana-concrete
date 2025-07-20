<style>
/* Responsive table styles with word wrapping */
#concreteReceiptsTable {
  font-size: 0.95rem;
  width: 100%;
}
#concreteReceiptsTable th, #concreteReceiptsTable td {
  padding: 0.3rem 0.4rem;
  word-break: break-word;
  white-space: normal;
  vertical-align: middle;
}
#concreteReceiptsTable th {
  font-size: 0.98rem;
  white-space: nowrap;
}
@media (max-width: 900px) {
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  #concreteReceiptsTable th, #concreteReceiptsTable td {
    font-size: 0.85rem;
    padding: 0.2rem 0.2rem;
  }
}
</style> 