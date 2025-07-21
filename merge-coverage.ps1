composer merge-coverage
# Launch browser
if (Get-Command "start" -ErrorAction SilentlyContinue) {
	start tests/phpcov/coverage-html/index.html
}
else {
	Invoke-Expression "tests/phpcov/coverage-html/index.html"
}
