FROM python:3.11.4

RUN apt-get update && apt-get install -y --no-install-recommends \
      python3-sphinx \
      make \
      latexmk \
      texlive-fonts-recommended \
      tex-gyre \
      texlive-latex-extra \
      texlive-latex-recommended; \
    rm -rf /var/lib/apt/lists/* \
USER 1000
RUN pip install 'git+https://github.com/bashtage/sphinx-material.git' \
      docxbuilder \
      myst-parser \
      myst-parser[linkify] \
      sphinx-autobuild;
