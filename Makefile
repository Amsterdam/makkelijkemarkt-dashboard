.PHONY: manifests deploy

dc = docker compose

ENVIRONMENT ?= local
HELM_ARGS = manifests/chart \
	-f manifests/values.yaml \
	-f manifests/env/${ENVIRONMENT}.yaml \
	--set image.tag=${VERSION}

REGISTRY ?= 127.0.0.1:5001
REPOSITORY ?= salmagundi/mm-dashboard
VERSION ?= latest
CHART_VERSION?=1.9.1

all: build push deploy fixtures

build:
	$(dc) build

test:
	echo "No tests available"

migrate:
	echo "No fixtures"

fixtures: migrate

push:
	$(dc) push


manifests:
	@helm template mm-dashboard $(HELM_ARGS) $(ARGS)

deploy: manifests
	helm upgrade --install mm-dashboard $(HELM_ARGS) $(ARGS)

update-chart:
	rm -rf manifests/chart
	git clone --branch $(CHART_VERSION) --depth 1 git@github.com:Amsterdam/helm-application.git manifests/chart
	rm -rf manifests/chart/.git

clean:
	$(dc) down -v --remove-orphans

reset:
	kubectl delete deployment mm-dashboard-mm-dashboard && kubectl delete deployment mm-dashboard-nginx-mm-dashboard && kubectl delete ingress mm-dashboard-nginx-internal-mm-dashboard && helm uninstall mm-dashboard

refresh: reset build push deploy

build-dev:
	$(dc) -f docker-compose-dev.yml build

dev:
	-docker network create markten
	$(dc) -f docker-compose-dev.yml up -d

dev-down:
	$(dc) -f docker-compose-dev.yml down
