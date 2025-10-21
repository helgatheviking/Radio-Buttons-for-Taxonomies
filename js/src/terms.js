/**
 * Returns terms in a tree form.
 *
 * @param {Array} flatTerms Array of terms in flat format.
 *
 * @return {Array} Array of terms in tree format.
 */
export function buildTermsTree( flatTerms ) {
	const flatTermsWithParentAndChildren = flatTerms.map( ( term ) => {
		return {
			children: [],
			parent: undefined,
			...term,
		};
	} );

	// All terms should have a `parent` because we're about to index them by it.
	if (
		flatTermsWithParentAndChildren.some(
			( { parent } ) => parent === undefined
		)
	) {
		return flatTermsWithParentAndChildren;
	}

	const termsByParent = flatTermsWithParentAndChildren.reduce(
		( acc, term ) => {
			const { parent } = term;
			if ( ! acc[ parent ] ) {
				acc[ parent ] = [];
			}
			acc[ parent ].push( term );
			return acc;
		},
		{}
	);

	const fillWithChildren = ( terms ) => {
		return terms.map( ( term ) => {
			const children = termsByParent[ term.id ];
			return {
				...term,
				children:
					children && children.length
						? fillWithChildren( children )
						: [],
			};
		} );
	};

	return fillWithChildren( termsByParent[ '0' ] || [] );
}
