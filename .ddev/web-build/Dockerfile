RUN composer global config --no-plugins allow-plugins.ion-bazan/composer-diff true; \
      composer global require ion-bazan/composer-diff --no-interaction;
#RUN python -m pip install --upgrade pip
# The Python 3.13 base image ships an empty `setuptools` namespace package with no
# RECORD file, so `pip install -U setuptools` fails (uninstall-no-record-file) and
# legacy setup.py builds (e.g. the mkdocs-edit-url git package below) die with
# "module 'setuptools' has no attribute 'setup'". Install a real setuptools + wheel
# into /usr/local (earlier on sys.path) with --ignore-installed so it shadows the stub.
RUN pip install --break-system-packages --ignore-installed "setuptools<81" wheel
RUN pip install --break-system-packages \
        mkdocs==1.5.3 \
        mkdocs-material \
        mike \
        mkdocs-git-authors-plugin \
        mkdocs-redirects \
        mkdocs-git-revision-date-localized-plugin \
        mkdocs-markdownextradata-plugin \
        pymdown-extensions \
        git+https://gitlab.com/blacs30/mkdocs-edit-url.git;
