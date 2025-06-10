/**
 * WordPress dependencies
 */
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import {
	Button,
	RadioControl,
	TextControl,
	TreeSelect,
	Flex,
	FlexItem,
	SearchControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useDebounce } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';
import { speak } from '@wordpress/a11y';
import { decodeEntities } from '@wordpress/html-entities';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { buildTermsTree } from './terms';

/**
 * Module Constants
 */
const DEFAULT_QUERY = {
	per_page: -1,
	orderby: 'name',
	order: 'asc',
	_fields: 'id,name,parent',
	context: 'view',
};

const MIN_TERMS_COUNT_FOR_FILTER = 8;

const EMPTY_ARRAY = [];

/**
 * Sort Terms by Selected.
 *
 * @param {Object[]} termsTree Array of terms in tree format.
 * @param {number[]} terms     Selected terms.
 *
 * @return {Object[]} Sorted array of terms.
 */
export function sortBySelected( termsTree, terms ) {
	const treeHasSelection = ( termTree ) => {
		if ( terms.indexOf( termTree.id ) !== -1 ) {
			return true;
		}
		if ( undefined === termTree.children ) {
			return false;
		}
		return (
			termTree.children
				.map( treeHasSelection )
				.filter( ( child ) => child ).length > 0
		);
	};
	const termOrChildIsSelected = ( termA, termB ) => {
		const termASelected = treeHasSelection( termA );
		const termBSelected = treeHasSelection( termB );

		if ( termASelected === termBSelected ) {
			return 0;
		}

		if ( termASelected && ! termBSelected ) {
			return -1;
		}

		if ( ! termASelected && termBSelected ) {
			return 1;
		}

		return 0;
	};
	const newTermTree = [ ...termsTree ];
	newTermTree.sort( termOrChildIsSelected );
	return newTermTree;
}

/**
 * Find term by parent id or name.
 *
 * @param {Object[]}      terms  Array of Terms.
 * @param {number|string} parent id.
 * @param {string}        name   Term name.
 * @return {Object} Term object.
 */
export function findTerm( terms, parent, name ) {
	return terms.find( ( term ) => {
		return (
			( ( ! term.parent && ! parent ) ||
				parseInt( term.parent ) === parseInt( parent ) ) &&
			term.name.toLowerCase() === name.toLowerCase()
		);
	} );
}

/**
 * Get filter matcher function.
 *
 * @param {string} filterValue Filter value.
 * @return {(function(Object): (Object|boolean))} Matcher function.
 */
export function getFilterMatcher( filterValue ) {
	const matchTermsForFilter = ( originalTerm ) => {
		if ( '' === filterValue ) {
			return originalTerm;
		}

		// Shallow clone, because we'll be filtering the term's children and
		// don't want to modify the original term.
		const term = { ...originalTerm };

		// Map and filter the children, recursive so we deal with grandchildren
		// and any deeper levels.
		if ( term.children.length > 0 ) {
			term.children = term.children
				.map( matchTermsForFilter )
				.filter( ( child ) => child );
		}

		// If the term's name contains the filterValue, or it has children
		// (i.e. some child matched at some point in the tree) then return it.
		if (
			-1 !==
				term.name.toLowerCase().indexOf( filterValue.toLowerCase() ) ||
			term.children.length > 0
		) {
			return term;
		}

		// Otherwise, return false. After mapping, the list of terms will need
		// to have false values filtered out.
		return false;
	};
	return matchTermsForFilter;
}

/**
 * Radio term selector.
 *
 * @param {Object} props      Component props.
 * @param {string} props.slug Taxonomy slug.
 * @return {Element}        Radio term selector component.
 */
export function RadioTermSelector( { slug } ) {
	const [ adding, setAdding ] = useState( false );
	const [ formName, setFormName ] = useState( '' );
	/**
	 * @type {[number|'', Function]}
	 */
	const [ formParent, setFormParent ] = useState( '' );
	const [ showForm, setShowForm ] = useState( false );
	const [ filterValue, setFilterValue ] = useState( '' );
	const [ filteredTermsTree, setFilteredTermsTree ] = useState( [] );
	const debouncedSpeak = useDebounce( speak, 500 );

	const {
		hasCreateAction,
		hasAssignAction,
		klass, // @helgatheviking.
		terms,
		loading,
		availableTerms,
		taxonomy,
	} = useSelect(
		( select ) => {
			const { getCurrentPost, getEditedPostAttribute } =
				select( editorStore );
			const { getEntityRecord, getEntityRecords, isResolving } =
				select( coreStore );
			const _taxonomy = getEntityRecord( 'root', 'taxonomy', slug );
			const post = getCurrentPost();

			return {
				hasCreateAction: _taxonomy
					? post._links?.[
							'wp:action-create-' + _taxonomy.rest_base
					  ] ?? false
					: false,
				hasAssignAction: _taxonomy
					? post._links?.[
							'wp:action-assign-' + _taxonomy.rest_base
					  ] ?? false
					: false,
				klass: taxonomy.hierarchical ? 'hierarchical' : 'non-hierarchical',
				terms: _taxonomy
					? getEditedPostAttribute( _taxonomy.rest_base )
					: EMPTY_ARRAY,
				loading: isResolving( 'getEntityRecords', [
					'taxonomy',
					slug,
					DEFAULT_QUERY,
				] ),
				availableTerms:
					getEntityRecords( 'taxonomy', slug, DEFAULT_QUERY ) ||
					EMPTY_ARRAY,
				taxonomy: _taxonomy,
			};
		},
		[ slug ]
	);

	const { editPost } = useDispatch( editorStore );
	const { saveEntityRecord } = useDispatch( coreStore );

	const availableTermsTree = useMemo(
		() => sortBySelected( buildTermsTree( availableTerms ), terms ),
		// Remove `terms` from the dependency list to avoid reordering every time
		// checking or unchecking a term.
		[ availableTerms ]
	);

	const { createErrorNotice } = useDispatch( noticesStore );

	if ( ! hasAssignAction ) {
		return null;
	}

	/**
	 * Append new term.
	 *
	 * @param {Object} term Term object.
	 * @return {Promise} A promise that resolves to save term object.
	 */
	const addTerm = ( term ) => {
		return saveEntityRecord( 'taxonomy', slug, term, {
			throwOnError: true,
		} );
	};

	/**
	 * Update terms for post.
	 *
	 * @param {number[]} termIds Term ids.
	 */
	const onUpdateTerms = ( termIds ) => {
		editPost( { [ taxonomy.rest_base ]: termIds } );
	};

	/**
	 * Handler for checking term.
	 *
	 * @param {number} termId
	 */
	const onChange = ( termId ) => {
		onUpdateTerms( [ termId ] ); // @helgatheviking - Limit to single term.
	};

	/**
	 * Handler for setting default term which clears all other terms.
	 */
	const onClear = () => {
		// @helgatheviking props @tomjn
		onUpdateTerms( [] );
	};

	const onChangeFormName = ( value ) => {
		setFormName( value );
	};

	/**
	 * Handler for changing form parent.
	 *
	 * @param {number|''} parentId Parent post id.
	 */
	const onChangeFormParent = ( parentId ) => {
		setFormParent( parentId );
	};

	const onToggleForm = () => {
		setShowForm( ! showForm );
	};

	const onAddTerm = async ( event ) => {
		event.preventDefault();
		if ( formName === '' || adding ) {
			return;
		}

		// Check if the term we are adding already exists.
		const existingTerm = findTerm( availableTerms, formParent, formName );
		if ( existingTerm ) {
			// If the term we are adding exists but is not selected select it.
			if ( ! terms.some( ( term ) => term === existingTerm.id ) ) {
				onUpdateTerms( [ existingTerm.id ] ); // @helgatheviking - Limit to single term.
			}

			setFormName( '' );
			setFormParent( '' );

			return;
		}
		setAdding( true );
		let newTerm;
		try {
			newTerm = await addTerm( {
				name: formName,
				parent: formParent ? formParent : undefined,
			} );
		} catch ( error ) {
			createErrorNotice( error.message, {
				type: 'snackbar',
			} );
			return;
		}
		const defaultName =
			slug === 'category' ? __( 'Category' ) : __( 'Term' );
		const termAddedMessage = sprintf(
			/* translators: %s: term name. */
			_x( '%s added', 'term' ),
			taxonomy?.labels?.singular_name ?? defaultName
		);
		speak( termAddedMessage, 'assertive' );
		setAdding( false );
		setFormName( '' );
		setFormParent( '' );
		onUpdateTerms( [ newTerm.id ] ); // @helgatheviking - Limit to single term.
	};

	const setFilter = ( value ) => {
		const newFilteredTermsTree = availableTermsTree
			.map( getFilterMatcher( value ) )
			.filter( ( term ) => term );
		const getResultCount = ( termsTree ) => {
			let count = 0;
			for ( let i = 0; i < termsTree.length; i++ ) {
				count++;
				if ( undefined !== termsTree[ i ].children ) {
					count += getResultCount( termsTree[ i ].children );
				}
			}
			return count;
		};

		setFilterValue( value );
		setFilteredTermsTree( newFilteredTermsTree );

		const resultCount = getResultCount( newFilteredTermsTree );
		const resultsFoundMessage = sprintf(
			/* translators: %d: number of results. */
			_n( '%d result found.', '%d results found.', resultCount ),
			resultCount
		);

		debouncedSpeak( resultsFoundMessage, 'assertive' );
	};

	const renderTerms = ( renderedTerms ) => {
		return renderedTerms.map( ( term ) => {
			const selected =
				terms.includes( term.id ) ||
				( ! terms.length && term.id === taxonomy.default_term )
					? term.id
					: 0;

			return (
				<div
					key={ term.id }
					className={ `radio-taxonomies-choice editor-post-taxonomies_${ klass }-terms-choice` }
				>
					<RadioControl
						selected={ selected }
						options={ [
							{
								label: decodeEntities( term.name ),
								value: term.id,
							},
						] }
						onChange={ () => {
							const termId = parseInt( term.id, 10 );
							onChange( termId );
						} }
					/>
					{ !! term.children.length && (
						<div className={ `editor-post-taxonomies__${ klass }-terms-subchoices` } >
							{ renderTerms( term.children ) }
						</div>
					) }
				</div>
			);
		} );
	};

	const labelWithFallback = (
		labelProperty,
		fallbackIsCategory,
		fallbackIsNotCategory
	) =>
		taxonomy?.labels?.[ labelProperty ] ??
		( slug === 'category' ? fallbackIsCategory : fallbackIsNotCategory );

	const newTermButtonLabel = labelWithFallback(
		'add_new_item',
		__( 'Add new category' ),
		__( 'Add new term' )
	);
	const newTermLabel = labelWithFallback(
		'new_item_name',
		__( 'Add new category' ),
		__( 'Add new term' )
	);
	const parentSelectLabel = labelWithFallback(
		'parent_item',
		__( 'Parent Category' ),
		__( 'Parent Term' )
	);
	const noParentOption = `— ${ parentSelectLabel } —`;
	const newTermSubmitLabel = newTermButtonLabel;
	const filterLabel = taxonomy?.labels?.search_items ?? __( 'Search Terms' );
	const groupLabel = taxonomy?.name ?? __( 'Terms' );
	const showFilter = availableTerms.length >= MIN_TERMS_COUNT_FOR_FILTER;

	// @helgatheviking - Add default term to the list.
	const noneSelected = terms.length ? 0 : -1;

	const singleTerm = labelWithFallback(
		'singular_name',
		__( 'Category' ),
		__( 'Term' )
	);

	const noTermLabel = sprintf(
		/* translators: %s: taxonomy name */
		_x( 'No %s', 'term', 'radio-buttons-for-taxonomies' ),
		singleTerm
	);

	return (
		<Flex direction="column" gap="4">
			{ showFilter && (
				<SearchControl
					__nextHasNoMarginBottom
					label={ filterLabel }
					placeholder={ filterLabel }
					value={ filterValue }
					onChange={ setFilter }
				/>
			) }
			<div
				className={ `editor-post-taxonomies__${ klass }-terms-list` }
				tabIndex="0"
				role="group"
				aria-label={ groupLabel }
			>
				{ renderTerms(
					'' !== filterValue ? filteredTermsTree : availableTermsTree
				) }

				{ /* @helgatheviking - Add default term to the list. */ }
				{ taxonomy.radio_no_term && (
					<div
						key={ `${ taxonomy }-no-term` }
						className={ `editor-post-taxonomies__${ klass }-terms-choice` }
					>
						<RadioControl
							selected={ noneSelected }
							options={ [ { label: noTermLabel, value: -1 } ] }
							onChange={ () => {
								onClear();
							} }
						/>
					</div>
				) }
			</div>
			{ ! loading && hasCreateAction && (
				<FlexItem>
					<Button
						__next40pxDefaultSize
						onClick={ onToggleForm }
						className={ `editor-post-taxonomies__${ klass }-terms-add` }
						aria-expanded={ showForm }
						variant="link"
					>
						{ newTermButtonLabel }
					</Button>
				</FlexItem>
			) }
			{ showForm && (
				<form onSubmit={ onAddTerm }>
					<Flex direction="column" gap="4">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							className={ `editor-post-taxonomies__${ klass }-terms-input` }
							label={ newTermLabel }
							value={ formName }
							onChange={ onChangeFormName }
							required
						/>
						{ taxonomy.hierarchical &&
							!! availableTerms.length && ( // @helgatheviking - Only show parent select if taxonomy is hierarchical.
								<TreeSelect
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ parentSelectLabel }
									noOptionLabel={ noParentOption }
									onChange={ onChangeFormParent }
									selectedId={ formParent }
									tree={ availableTermsTree }
								/>
							) }
						<FlexItem>
							<Button
								__next40pxDefaultSize
								variant="secondary"
								type="submit"
								className={ `editor-post-taxonomies__${ klass }-terms-submit` }
							>
								{ newTermSubmitLabel }
							</Button>
						</FlexItem>
					</Flex>
				</form>
			) }
		</Flex>
	);
}

export default RadioTermSelector;
