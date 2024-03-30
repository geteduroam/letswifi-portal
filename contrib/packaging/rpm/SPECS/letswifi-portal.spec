%define git 6b7043d5a6aae05e0c06e4e706a5365eff9daf73
%define php_openssl 34ef596055287c72a110e98f0467aeb5f5386c65
%define php_oauth_server 54cc590aaf893d5d2a5c9cef3427b19404056b93

Name:       letswifi-portal
Version:    1.0.0
Release:    1%{?dist}
Summary:    User portal for Let's Wi-Fi enrollment and management
Group:      Applications/Internet
License:    BSD-3-Clause
URL:        https://github.com/geteduroam/letswifi-portal
%if %{defined git}
Source0:    https://github.com/geteduroam/letswifi-portal/archive/%{git}.tar.gz
%else
Source0:    https://github.com/geteduroam/ionic-app/archive/refs/tags/v%{version}.tar.gz
%endif
Source1:    https://git.sr.ht/~jornane/php-openssl/archive/%{php_openssl}.tar.gz
Source2:    https://git.sr.ht/~jornane/php-oauth-server/archive/%{php_oauth_server}.tar.gz
Source3:    autoload.php
Source4:    letswifi.conf.php
Source5:    letswifi-portal-httpd.conf

BuildArch:  noarch

Requires:   httpd-filesystem
Requires:   php-cli
Requires:   php(language) >= 7.3
Requires:   php-composer(fedora/autoloader)
Requires:   php-curl
Requires:   php-date
Requires:   php-hash
Requires:   php-json
Requires:   php-mbstring
Requires:   php-openssl
Requires:   php-pcre
Requires:   php-pdo
Requires:   php-pdo_sqlite
Requires:   php-spl
Requires:   php-twig3

Requires(post): /usr/sbin/semanage
Requires(postun): /usr/sbin/semanage

Suggests:  php-pdo_mysql

%description
User portal for Let's Wi-Fi installations, which allows users to obtain
pseudo-credentials for connecting to an 802.1x network.

%prep
%if %{defined git}
%setup -qn letswifi-portal-%{git}
%else
%setup -qn letswifi-portal-%{version}
%endif

%build
echo "%{version}-%{release}" > VERSION

%install
mkdir -p %{buildroot}%{_datadir}/letswifi-portal/www
cp -pr www/* %{buildroot}%{_datadir}/letswifi-portal/www
mkdir -p %{buildroot}%{_datadir}/letswifi-portal/tpl
cp -pr tpl/* %{buildroot}%{_datadir}/letswifi-portal/tpl
mkdir -p %{buildroot}%{_datadir}/php/letswifi
cp -pr src/letswifi/* %{buildroot}%{_datadir}/php/letswifi
mkdir -p %{buildroot}%{_sysconfdir}/letswifi-portal
cp -pr etc/clients.php %{buildroot}%{_sysconfdir}/letswifi-portal

mkdir -p %{buildroot}%{_localstatedir}/lib/letswifi-portal

mkdir -p %{buildroot}%{_datadir}/php/fyrkat/openssl
mkdir -p %{buildroot}%{_datadir}/php/fyrkat/oauth

tar --strip-components=4 -C %{buildroot}%{_datadir}/php/fyrkat/openssl -xzvf %{SOURCE1} php-openssl-%{php_openssl}/src/fyrkat/openssl
tar --strip-components=4 -C %{buildroot}%{_datadir}/php/fyrkat/oauth -xzvf %{SOURCE2} php-oauth-server-%{php_oauth_server}/src/fyrkat/oauth

install -m 0644 -D -p %{SOURCE3} %{buildroot}%{_datadir}/letswifi-portal/_autoload.php
install -m 0640 -D -p %{SOURCE4} %{buildroot}%{_sysconfdir}/letswifi-portal/letswifi.conf.php
install -m 0644 -D -p %{SOURCE5} %{buildroot}%{_sysconfdir}/httpd/conf.d/letswifi-portal.conf

mkdir -p %{buildroot}%{_localstatedir}/lib/letswifi

%pre
	semanage fcontext -a -t httpd_sys_rw_content_t '%{_localstatedir}/lib/letswifi-portal(/.*)?' 2>/dev/null || :
%post
	restorecon -R %{_localstatedir}/lib/letswifi-portal || :

%postun
	if [ $1 -eq 0 ] ; then  # final removal
	semanage fcontext -d -t httpd_sys_rw_content_t '%{_localstatedir}/lib/letswifi-portal(/.*)?' 2>/dev/null || :
	fi

%files
%defattr(-,root,root,-)
%config(noreplace) %{_sysconfdir}/httpd/conf.d/letswifi-portal.conf
%dir %attr(2750,root,apache) %{_sysconfdir}/letswifi-portal
%config %{_sysconfdir}/letswifi-portal/clients.php
%config(noreplace) %attr(2750,root,apache) %{_sysconfdir}/letswifi-portal/letswifi.conf.php
%{_datadir}/php/letswifi
%{_datadir}/php/fyrkat/openssl
%{_datadir}/php/fyrkat/oauth
%{_datadir}/letswifi-portal
%dir %attr(0700,apache,apache) %{_localstatedir}/lib/letswifi
%license COPYING

%changelog
* Sat Apr 01 2023 Jørn Åne de Jong <jorn.de.jong@letswifi.eu> - 1.0.0-1
- initial release 1.0.0
