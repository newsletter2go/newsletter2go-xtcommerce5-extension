version = 0_0_00
outfile = XtCommerce_nl2go_$(version).zip

$(outfile):
	zip -r  build.zip ./xt_newsletter2go/*
	mv build.zip $(outfile)

clean:
	rm -rf $(outfile)
