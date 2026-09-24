FROM ubuntu:24.04@sha256:008173c23f95b170204355c12626cb5a965d779a7e1283b09e9cffbb1bf33ca3

ARG RUNNER_VERSION=2.337.0
ARG RUNNER_SHA256=70920811a4f8ad4328818682bca5c6469c1c942fab52448868071d0063816613

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
	&& apt-get install --yes --no-install-recommends \
		ca-certificates \
		curl \
		git \
		gzip \
		jq \
		libicu74 \
		libkrb5-3 \
		libssl3 \
		openssh-client \
		python3 \
		tar \
		zlib1g \
	&& rm -rf /var/lib/apt/lists/* \
	&& useradd --create-home --uid 10001 --shell /bin/bash runner \
	&& install -d -o runner -g runner /opt/actions-runner /runner /deploy-cache \
	&& curl --fail --location --silent --show-error \
		--output /tmp/actions-runner.tar.gz \
		"https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-linux-x64-${RUNNER_VERSION}.tar.gz" \
	&& echo "${RUNNER_SHA256}  /tmp/actions-runner.tar.gz" | sha256sum --check --strict \
	&& tar --extract --gzip --file /tmp/actions-runner.tar.gz --directory /opt/actions-runner \
	&& rm /tmp/actions-runner.tar.gz \
	&& chown -R runner:runner /opt/actions-runner /runner /deploy-cache

COPY --chmod=755 entrypoint.sh /usr/local/bin/smps-runner-entrypoint

USER runner
WORKDIR /runner

ENTRYPOINT ["/usr/local/bin/smps-runner-entrypoint"]
CMD ["run"]
