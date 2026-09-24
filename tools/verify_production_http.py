#!/usr/bin/env python3
"""Run bounded public HTTP checks after a production file deployment."""

from __future__ import annotations

import argparse
import sys
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, file_pointer, code, message, headers, new_url):
        return None


def probe(base_url: str, path: str, allowed: set[int], timeout: int) -> None:
    url = urllib.parse.urljoin(base_url, path.lstrip("/"))
    request = urllib.request.Request(
        url,
        method="GET",
        headers={"User-Agent": "SMPS-production-verifier/2.0"},
    )
    opener = urllib.request.build_opener(NoRedirect)
    try:
        response = opener.open(request, timeout=timeout)
    except urllib.error.HTTPError as exc:
        response = exc
    try:
        status = response.status
        if status not in allowed:
            raise RuntimeError(
                f"Unexpected HTTP status {status} for {path}; expected {sorted(allowed)}."
            )
        if 300 <= status < 400:
            location = response.headers.get("Location")
            if not location:
                raise RuntimeError(f"Redirect response for {path} has no Location header.")
            redirect_url = urllib.parse.urljoin(base_url, location)
            base = urllib.parse.urlsplit(base_url)
            redirect = urllib.parse.urlsplit(redirect_url)
            if (redirect.scheme, redirect.hostname, redirect.port) != (
                base.scheme,
                base.hostname,
                base.port,
            ):
                raise RuntimeError(f"Redirect response for {path} leaves the production origin.")
        print(f"HTTP {status} {path}")
    finally:
        response.close()


def verify(base_url: str, timeout: int = 20) -> None:
    parsed = urllib.parse.urlsplit(base_url)
    if parsed.scheme != "https" or not parsed.netloc or parsed.query or parsed.fragment:
        raise ValueError("Base URL must be an HTTPS origin without a query or fragment.")
    normalized = base_url.rstrip("/") + "/"

    probe(normalized, "/", {200, 301, 302, 303, 307, 308}, timeout)
    probe(normalized, "/favicon.ico", {200}, timeout)
    protected_paths = (
        "/.git/HEAD",
        "/composer.json",
        "/config/common.neon",
        "/config/local.neon",
        "/config/phinx.php",
        "/db/",
        "/log/",
        "/temp/",
        "/vendor/autoload.php",
        "/www/dokumenty/",
        "/RELEASE_SHA",
        "/RELEASE_MANIFEST.sha256",
    )
    for path in protected_paths:
        probe(normalized, path, {403, 404}, timeout)
    probe(normalized, "/sign/in", {301, 302, 303, 307, 308}, timeout)
    probe(normalized, "/authentication/login", {200}, timeout)
    probe(normalized, "/www/index.php", {403, 404}, timeout)


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-url", required=True)
    parser.add_argument("--timeout", type=int, default=20)
    args = parser.parse_args(argv)
    try:
        if args.timeout < 1 or args.timeout > 120:
            raise ValueError("Timeout must be from 1 to 120 seconds.")
        verify(args.base_url, args.timeout)
    except (OSError, RuntimeError, ValueError) as exc:
        print(f"Production HTTP verification failed: {exc}", file=sys.stderr)
        return 1
    print("Production HTTP acceptance passed. Response bodies were not read.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
