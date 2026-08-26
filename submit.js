<script>
function checkform() {
  const formElements = document.forms["isian"].elements;
  let submitBtnActive = true;

  for (let inputEl = 0; inputEl < formElements.length; inputEl++) {
    if (formElements[inputEl].value.length == 0) submitBtnActive = false;
  }

  if (submitBtnActive) {
    document.getElementById("submit").disabled = false;
  } else {
    document.getElementById("submit").disabled = "disabled";
  }
}
</script>
