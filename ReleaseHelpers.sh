#!/bin/bash

# arguments: VERSION, BRANCH, BUILD_URL, DIR (optional)
function tag_version {
	local VERSION=$1
	local BRANCH=$2
	local BUILD_URL=$3
	local DIR=$4

	if [ -z "${DIR}" ] ; then
		git tag -f -a -m "[TASK] Tag as version ${VERSION}" -m "See ${BUILD_URL}" -m "Releases: ${BRANCH}" ${VERSION}
	else
		git --git-dir "${DIR}/.git" tag -f -a -m "[TASK] Tag as version ${VERSION}" -m "See ${BUILD_URL}" -m "Releases: ${BRANCH}" ${VERSION}
	fi
}

# arguments: TAG, DIR (optional)
function push_tag {
	local TAG=$1
	local DIR=$2

	if [ -z "${DIR}" ] ; then
		git push -f origin ${TAG}:refs/tags/${TAG}
	else
		git --git-dir "${DIR}/.git" push -f origin ${TAG}:refs/tags/${TAG}
	fi
}

# arguments: BRANCH, BUILD_URL, DIR (optional)
function commit_manifest_update {
	local BRANCH=$1
	local BUILD_URL=$2
	local DIR=$3

	if [ -z "${DIR}" ] ; then
		if [[ `git status --porcelain composer.json` ]] ; then
			git add composer.json
			git commit -m "[TASK] Update composer manifest" -m "See ${BUILD_URL}" -m "Releases: ${BRANCH}"
		fi
	else
		if [[ `git --git-dir "${DIR}/.git" --work-tree "${DIR}" status --porcelain composer.json` ]] ; then
			git --git-dir "${DIR}/.git" --work-tree "${DIR}" add composer.json
			git --git-dir "${DIR}/.git" --work-tree "${DIR}" commit -m "[TASK] Update composer manifest" -m "See ${BUILD_URL}" -m "Releases: ${BRANCH}"
		fi
	fi

}

# arguments: BRANCH, DIR (optional)
function push_branch {
	local BRANCH=$1
	local DIR=$2

	if [ -z "${DIR}" ] ; then
		git push origin ${BRANCH}
	else
		git --git-dir "${DIR}/.git" push origin ${BRANCH}
	fi
}
